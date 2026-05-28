const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const inputDir = path.join(__dirname, '../assets/images');
const outputDir = path.join(__dirname, '../public/assets/images');

if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

fs.readdirSync(inputDir).forEach(file => {
  const ext = path.extname(file).toLowerCase();
  
  if (['.jpg', '.jpeg', '.png', '.webp'].includes(ext)) {
    sharp(path.join(inputDir, file))
      .toFile(path.join(outputDir, file))
      .then(() => console.log(`✅ Optimized ${file}`))
      .catch(err => console.error(`❌ Error on ${file}:`, err));
  }
});