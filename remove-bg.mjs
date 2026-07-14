import sharp from 'sharp';

const src  = 'C:/Users/USER/Downloads/e3f97049-d0d2-4acf-9417-8c35e77a2e8b.png';
const dest = 'C:/Users/USER/OneDrive/Desktop/AI CROP DETECTOR/msas-system/public/images/msas-logo.png';

const { data, info } = await sharp(src)
    .ensureAlpha()
    .raw()
    .toBuffer({ resolveWithObject: true });

const { width, height, channels } = info;

for (let i = 0; i < data.length; i += channels) {
    const r = data[i], g = data[i+1], b = data[i+2];
    const brightness = (r + g + b) / 3;

    // Black / near-black background → fully transparent
    if (brightness < 18) {
        data[i+3] = 0;
    }
}

await sharp(data, { raw: { width, height, channels } })
    .png()
    .toFile(dest);

console.log('Done → msas-logo.png (black background removed)');
