import sharp from "sharp";
import path from "path";
import fs from "fs/promises";

export class ImageProcessor {
  private uploadsDir = path.join(process.cwd(), "uploads");
  private backupsDir = path.join(process.cwd(), "backups");

  constructor() {
    this.ensureDirectories();
  }

  private async ensureDirectories() {
    try {
      await fs.mkdir(this.uploadsDir, { recursive: true });
      await fs.mkdir(this.backupsDir, { recursive: true });
    } catch (error) {
      console.error("Error creating directories:", error);
    }
  }

  async processAndWatermarkImage(buffer: Buffer, filename: string): Promise<string> {
    const processedFilename = `processed_${Date.now()}_${filename}`;
    const outputPath = path.join(this.uploadsDir, processedFilename);

    // Create watermark text overlay
    const watermarkSvg = `
      <svg width="800" height="600">
        <defs>
          <pattern id="watermark" patternUnits="userSpaceOnUse" width="400" height="300" patternTransform="rotate(45)">
            <text x="0" y="150" font-family="Arial, sans-serif" font-size="24" font-weight="bold" 
                  fill="rgba(0,0,0,0.3)" opacity="0.5">FOR DRIVE KL EXECUTIVE SDN BHD USE ONLY</text>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#watermark)"/>
      </svg>
    `;

    await sharp(buffer)
      .resize(1200, 1200, { 
        fit: 'inside',
        withoutEnlargement: true 
      })
      .jpeg({ quality: 80 })
      .composite([{
        input: Buffer.from(watermarkSvg),
        blend: 'overlay'
      }])
      .toFile(outputPath);

    return `/uploads/${processedFilename}`;
  }

  async processSignature(dataUrl: string, filename: string): Promise<string> {
    const base64Data = dataUrl.replace(/^data:image\/\w+;base64,/, "");
    const buffer = Buffer.from(base64Data, 'base64');
    
    const processedFilename = `signature_${Date.now()}_${filename}`;
    const outputPath = path.join(this.uploadsDir, processedFilename);

    await sharp(buffer)
      .png()
      .toFile(outputPath);

    return `/uploads/${processedFilename}`;
  }

  async processVehiclePhoto(buffer: Buffer, filename: string): Promise<string> {
    const processedFilename = `vehicle_${Date.now()}_${filename}`;
    const outputPath = path.join(this.uploadsDir, processedFilename);

    await sharp(buffer)
      .resize(800, 600, { 
        fit: 'inside',
        withoutEnlargement: true 
      })
      .jpeg({ quality: 85 })
      .toFile(outputPath);

    return `/uploads/${processedFilename}`;
  }
}

export const imageProcessor = new ImageProcessor();
