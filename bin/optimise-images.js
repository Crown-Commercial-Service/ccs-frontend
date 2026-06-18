const sharp = require('sharp');
const { optimize } = require('svgo'); 
const fs = require('fs');
const path = require('path');

const inputDir = path.join(__dirname, '../assets/images');
const outputDir = path.join(__dirname, '../public/assets/images');

if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

fs.readdirSync(inputDir).forEach(file => {
  const ext = path.extname(file).toLowerCase();
  const inputPath = path.join(inputDir, file);
  const outputPath = path.join(outputDir, file);
  
  // 1. Optimize Raster Images
  if (['.jpg', '.jpeg', '.png', '.webp'].includes(ext)) {
    sharp(inputPath)
      .toFile(outputPath)
      .then(() => console.log(`✅ Optimized ${file}`))
      .catch(err => console.error(`❌ Error on ${file}:`, err));
  } 
  // 2. Minify and Transfer SVGs
  else if (ext === '.svg') {
    try {
      const svgString = fs.readFileSync(inputPath, 'utf8');
      const result = optimize(svgString, {
        path: inputPath,
        multipass: true
      });
      fs.writeFileSync(outputPath, result.data);
      console.log(`✨ Minified & Transferred SVG: ${file}`);
    } catch (err) {
      console.error(`❌ Error optimizing SVG ${file}:`, err);
    }
  }
  // 3. Copy non-optimized file types like favicon.ico
  else if (['.ico', '.gif', '.bmp', '.tiff'].includes(ext)) {
    try {
      fs.copyFileSync(inputPath, outputPath);
      console.log(`📄 Copied ${file}`);
    } catch (err) {
      console.error(`❌ Error copying ${file}:`, err);
    }
  } else {
    console.log(`⏭ Skipping unsupported file type: ${file}`);
  }
});