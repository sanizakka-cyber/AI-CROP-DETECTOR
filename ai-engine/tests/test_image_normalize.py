"""
Regression tests for _normalize_image_bytes() (main.py) -- the Phase 7 fix
for a real production failure: a 2.95MB diagnostic photo was rejected by
PHP's upload_max_filesize before Laravel ever saw it, and even once that's
fixed, nothing previously guaranteed the base64 payload forwarded to Claude
stayed under Anthropic's real 10MB-base64-encoded per-image limit. This
tests the guarantee directly: every image handed to Claude is resized to a
sane dimension, recompressed as JPEG, and kept well under that limit,
regardless of what was uploaded -- and a genuinely undecodable file is
rejected honestly (422) rather than silently forwarded or faked into a
diagnosis.

Run: pip install -r requirements.txt pytest && pytest tests/ -v
"""
import io
import sys
import os

import pytest
from PIL import Image, ExifTags
from fastapi import HTTPException

sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
from main import _normalize_image_bytes, _MAX_LONG_EDGE, _MAX_FORWARD_BYTES  # noqa: E402


def _make_jpeg(width: int, height: int, color=(120, 160, 90), quality: int = 90) -> bytes:
    img = Image.new("RGB", (width, height), color)
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=quality)
    return buf.getvalue()


def _make_png_with_alpha(width: int, height: int) -> bytes:
    img = Image.new("RGBA", (width, height), (10, 200, 30, 128))
    buf = io.BytesIO()
    img.save(buf, format="PNG")
    return buf.getvalue()


class TestValidImages:
    def test_small_valid_jpeg_passes_through_decodable(self):
        """A normal, well-under-limit photo is still returned as a valid, decodable JPEG."""
        raw = _make_jpeg(800, 600)
        out = _normalize_image_bytes(raw)
        with Image.open(io.BytesIO(out)) as im:
            assert im.format == "JPEG"
            assert im.size == (800, 600)

    def test_oversized_dimensions_are_capped_to_max_long_edge(self):
        """A huge image (e.g. a 50MP phone camera shot) is resized so neither edge exceeds the cap."""
        raw = _make_jpeg(6000, 4000)
        out = _normalize_image_bytes(raw)
        with Image.open(io.BytesIO(out)) as im:
            assert max(im.size) <= _MAX_LONG_EDGE
            # Aspect ratio preserved (within rounding).
            assert abs((im.width / im.height) - (6000 / 4000)) < 0.01

    def test_output_always_stays_under_the_claude_safe_byte_ceiling(self):
        """Regardless of input size, the forwarded payload never exceeds the safe raw-byte ceiling."""
        raw = _make_jpeg(6000, 4000, color=(200, 50, 10), quality=100)
        out = _normalize_image_bytes(raw)
        assert len(out) <= _MAX_FORWARD_BYTES

    def test_small_image_is_not_upscaled(self):
        """An image already under the long-edge cap is left at its original dimensions, not enlarged."""
        raw = _make_jpeg(400, 300)
        out = _normalize_image_bytes(raw)
        with Image.open(io.BytesIO(out)) as im:
            assert im.size == (400, 300)

    def test_png_with_alpha_channel_converts_to_rgb_jpeg_without_error(self):
        """JPEG has no alpha channel -- an RGBA PNG must be flattened to RGB, not crash."""
        raw = _make_png_with_alpha(500, 500)
        out = _normalize_image_bytes(raw)
        with Image.open(io.BytesIO(out)) as im:
            assert im.mode == "RGB"
            assert im.format == "JPEG"

    def test_exif_rotation_is_baked_into_pixel_data_not_dropped(self):
        """
        A portrait phone photo stores rotation as an EXIF tag, not in the
        pixel data. Re-encoding without applying it first would silently
        hand Claude a sideways image with the orientation hint gone.
        """
        # A landscape 800x600 buffer tagged "rotate 90" (EXIF orientation 6)
        # must become a portrait 600x800 image after normalization.
        img = Image.new("RGB", (800, 600), (50, 120, 200))
        exif = img.getexif()
        orientation_tag = next(k for k, v in ExifTags.TAGS.items() if v == "Orientation")
        exif[orientation_tag] = 6  # "rotate 90 CW on display"
        buf = io.BytesIO()
        img.save(buf, format="JPEG", exif=exif)
        out = _normalize_image_bytes(buf.getvalue())
        with Image.open(io.BytesIO(out)) as im:
            assert im.size == (600, 800), "EXIF orientation must be applied to pixel data, not left to the viewer."


class TestInvalidImages:
    def test_completely_corrupt_bytes_raise_422_not_a_silent_pass_through(self):
        garbage = b"this is not an image file at all, just plain text bytes"
        with pytest.raises(HTTPException) as exc_info:
            _normalize_image_bytes(garbage)
        assert exc_info.value.status_code == 422

    def test_truncated_jpeg_raises_422(self):
        """A JPEG with a valid header but truncated body must fail honestly, not forward broken bytes."""
        raw = _make_jpeg(800, 600)
        truncated = raw[: len(raw) // 3]
        with pytest.raises(HTTPException):
            _normalize_image_bytes(truncated)

    def test_empty_bytes_raise_422(self):
        with pytest.raises(HTTPException) as exc_info:
            _normalize_image_bytes(b"")
        assert exc_info.value.status_code == 422
