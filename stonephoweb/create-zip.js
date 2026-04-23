import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Simple ZIP creation without external dependencies
// Since archiver isn't available, let's create a simple tar.gz instead
import { exec } from 'child_process';
import { promisify } from 'util';

const execAsync = promisify(exec);

async function createArchive() {
  try {
    console.log('Creating archive...');
    
    // Create a tar.gz file with all the source files
    const command = `tar -czf stone-pho-website.tar.gz --exclude=node_modules --exclude=dist --exclude=.git --exclude=stone-pho-website.tar.gz src/ public/ index.html package.json tsconfig*.json vite.config.ts tailwind.config.js postcss.config.js eslint.config.js README.md`;
    
    await execAsync(command);
    console.log('Archive created successfully: stone-pho-website.tar.gz');
    
    // List files to confirm
    const { stdout } = await execAsync('ls -la *.tar.gz');
    console.log('Archive files:', stdout);
    
  } catch (error) {
    console.error('Error creating archive:', error.message);
    
    // Fallback: create a simple text file with instructions
    const instructions = `# Stone Pho Website Source Code

This project contains a complete Vietnamese restaurant website built with:
- React + TypeScript
- Tailwind CSS
- Device detection system
- Responsive design

## To download the source code:
1. Use the file explorer in your development environment
2. Select all project files (src/, public/, package.json, etc.)
3. Download them individually or use your IDE's export feature

## Key files:
- src/App.tsx - Main application component
- src/components/ - All React components
- src/hooks/useDeviceDetection.ts - Device detection hook
- package.json - Dependencies and scripts
- tailwind.config.js - Tailwind configuration
- vite.config.ts - Vite configuration

## To run locally:
1. npm install
2. npm run dev

## To build for production:
1. npm run build
`;

    fs.writeFileSync('DOWNLOAD_INSTRUCTIONS.txt', instructions);
    console.log('Created DOWNLOAD_INSTRUCTIONS.txt with manual download instructions');
  }
}

createArchive();