#!/usr/bin/env node
/**
 * Package builder for bloecks addon
 * Replaces the functionality of zip.sh
 */

import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';

console.log('🚀 Building release package...');

try {
    // Run production build first
    console.log('📦 Running production build...');
    execSync('pnpm run build:prod', { stdio: 'inherit' });
    
    // Create zip file excluding development files
    const excludePatterns = [
        'node_modules/',
        'assets_src/',
        '.git/',
        '.github/',
        '*.log',
        'package-lock.json',
        'Gruntfile.js',
        'scripts/',
        '*.sh',
        '.gitignore',
        'README.developers.md'
    ];
    
    const excludeArgs = excludePatterns.map(pattern => `--exclude='${pattern}'`).join(' ');
    const zipCommand = `cd .. && zip -r bloecks.zip bloecks ${excludeArgs}`;
    
    console.log('🗜️  Creating zip file...');
    execSync(zipCommand, { stdio: 'inherit' });
    
    console.log('✅ Package created: ../bloecks.zip');
    console.log('\n📋 Package contents exclude:');
    excludePatterns.forEach(pattern => console.log(`  - ${pattern}`));
    
} catch (error) {
    console.error('❌ Error creating package:', error.message);
    process.exit(1);
}