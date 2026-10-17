"""
Phase 12.1: HEIC/HEIF decoding, the /convert/image fallback used by Laravel,
colour-profile handling, and that a photo sent as HEIC reaches the model
almost identical to the same photo sent as JPEG.
"""
import io
import math
import os
import struct
import sys

import pytest
from PIL import Image, ImageCms, ImageDraw

os.environ.setdefault("ANTHROPIC_API_KEY", "test-key")
sys.path.insert(0, os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

import pillow_heif  # noqa: E402
import main  # noqa: E402
from fastapi.testclient import TestClient  # noqa: E402


def leaf(w=1200, h=800):
    """A detailed, colourful test image (gradients + shapes), so compression differences show."""
    im = Image.new("RGB", (w, h))
    px = im.load()
    for y in range(h):
        for x in range(w):
            px[x, y] = (int(40 + 120 * x / w), int(90 + 140 * y / h), int(30 + 60 * ((x + y) % 97) / 97))
    d = ImageDraw.Draw(im)
    for i in range(25):
        d.ellipse((40 * i, 30 + 25 * i, 40 * i + 70, 80 + 25 * i), fill=(110 + 4 * i, 70, 40))
    return im


def as_jpeg(im, q=95, **kw):
    b = io.BytesIO()
    im.save(b, "JPEG", quality=q, **kw)
    return b.getvalue()


def as_heic(im, q=90, exif=None):
    b = io.BytesIO()
    kw = {"quality": q}
    if exif is not None:
        kw["exif"] = exif
    im.save(b, format="HEIF", **kw)
    return b.getvalue()


def psnr(a, b):
    a, b = a.convert("RGB"), b.convert("RGB").resize(a.size)
    pa, pb = a.tobytes(), b.tobytes()
    mse = sum((x - y) ** 2 for x, y in zip(pa[::7], pb[::7])) / len(pa[::7])
    return 99.0 if mse == 0 else 10 * math.log10(255 ** 2 / mse)


@pytest.fixture
def client(monkeypatch):
    monkeypatch.setattr(main, "API_KEY", "")
    return TestClient(main.app)


def test_heic_is_supported():
    assert main.HEIF_SUPPORTED


def test_scan_pipeline_accepts_heic():
    out = Image.open(io.BytesIO(main._normalize_image_bytes(as_heic(leaf()))))
    assert out.format == "JPEG" and out.size == (1200, 800)


def test_convert_endpoint_returns_a_jpeg_with_orientation_applied(client):
    exif = Image.Exif()
    exif[0x0112] = 6  # rotate 90° clockwise for display (portrait phone photo)
    r = client.post("/convert/image", files={"file": ("IMG_0042.HEIC", as_heic(leaf(), exif=exif.tobytes()), "image/heic")})
    assert r.status_code == 200 and r.headers["content-type"] == "image/jpeg"
    out = Image.open(io.BytesIO(r.content))
    assert out.format == "JPEG"
    assert out.size == (800, 1200)  # portrait, as the farmer saw it


def test_convert_endpoint_rejects_non_images_and_empty_files(client):
    assert client.post("/convert/image", files={"file": ("x.heic", b"not an image", "image/heic")}).status_code == 422
    assert client.post("/convert/image", files={"file": ("x.heic", b"", "image/heic")}).status_code == 422


def test_convert_caps_very_large_photos(client):
    r = client.post("/convert/image", files={"file": ("big.heic", as_heic(leaf(5000, 3000), q=50), "image/heic")})
    assert max(Image.open(io.BytesIO(r.content)).size) == 4096


def _s15(v):
    return struct.pack(">i", int(round(v * 65536)))


def display_p3_icc():
    """A minimal ICC v2 matrix/curve RGB profile with Display P3 primaries
    (D50-adapted, as phones embed them) and gamma 2.2."""
    def xyz(x, y, z):
        return b"XYZ " + b"\0" * 4 + _s15(x) + _s15(y) + _s15(z)

    curv = b"curv" + b"\0" * 4 + struct.pack(">IH", 1, int(round(2.2 * 256))) + b"\0\0"
    tags = [(b"wtpt", xyz(0.9642, 1.0, 0.8249)), (b"rXYZ", xyz(0.5151, 0.2412, -0.0011)),
            (b"gXYZ", xyz(0.2920, 0.6922, 0.0419)), (b"bXYZ", xyz(0.1571, 0.0666, 0.7841)),
            (b"rTRC", curv), (b"gTRC", curv), (b"bTRC", curv)]
    start = 128 + 4 + 12 * len(tags)
    table, data = b"", b""
    for sig, body in tags:
        table += sig + struct.pack(">II", start + len(data), len(body))
        data += body + b"\0" * (-len(body) % 4)
    header = (struct.pack(">I", start + len(data)) + b"\0" * 4 + struct.pack(">I", 0x02100000)
              + b"mntrRGB XYZ " + b"\0" * 12 + b"acsp" + b"\0" * 28
              + _s15(0.9642) + _s15(1.0) + _s15(0.8249) + b"\0" * 48)
    assert len(header) == 128
    return header + struct.pack(">I", len(tags)) + table + data


def test_an_embedded_display_p3_profile_is_converted_to_srgb():
    p3 = ImageCms.ImageCmsProfile(io.BytesIO(display_p3_icc()))
    src = Image.new("RGB", (64, 64), (200, 40, 40))  # a strong red, as a P3 phone photo stores it
    tagged = as_jpeg(src, q=100, icc_profile=display_p3_icc())
    expected = ImageCms.profileToProfile(src, p3, main._SRGB, outputMode="RGB")
    out = Image.open(io.BytesIO(main._normalize_image_bytes(tagged)))
    r, g, b = out.getpixel((32, 32))
    er, eg, eb = expected.getpixel((32, 32))
    assert abs(r - er) <= 3 and abs(g - eg) <= 3 and abs(b - eb) <= 3  # follows the profile...
    assert r > 200                                                     # ...so the red stays as vivid as on the phone,
    assert (r, g, b) != src.getpixel((0, 0))                           # instead of P3 numbers read as sRGB


def test_srgb_or_untagged_photos_are_unchanged_by_colour_handling():
    src = leaf(400, 300)
    srgb = ImageCms.ImageCmsProfile(main._SRGB).tobytes()
    for data in (as_jpeg(src), as_jpeg(src, icc_profile=srgb)):
        assert psnr(Image.open(io.BytesIO(main._normalize_image_bytes(data))), src) > 33


def test_heic_and_jpeg_of_the_same_photo_reach_the_model_nearly_identical():
    """App path: HEIC -> JPEG on the phone (q 0.9) -> engine. Web path: HEIC ->
    /convert/image -> engine. Both must match the JPEG path closely."""
    src = leaf()
    via_jpeg = Image.open(io.BytesIO(main._normalize_image_bytes(as_jpeg(src, q=85))))
    heic = as_heic(src, q=90)
    phone_converted = as_jpeg(Image.open(io.BytesIO(heic)).convert("RGB"), q=90)
    via_app = Image.open(io.BytesIO(main._normalize_image_bytes(phone_converted)))
    via_server = Image.open(io.BytesIO(main._normalize_image_bytes(main.convert_to_jpeg(heic))))
    assert via_app.size == via_jpeg.size == via_server.size
    assert psnr(via_app, via_jpeg) > 32
    assert psnr(via_server, via_jpeg) > 32
