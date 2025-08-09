#!/usr/bin/env node
/**
 * Version updater for bloecks addon
 * Replaces the functionality of version.sh but works on all platforms
 */

import fs from 'fs';
import path from 'path';

const version = process.argv[2];

if (!version) {
    console.error('Usage: pnpm run version <version>');
    console.error('Example: pnpm run version "4.0.3"');
    process.exit(1);
}

console.log(`Updating version to ${version}...`);

try {
    // Update package.yml
    const packageYmlPath = 'package.yml';
    let packageYml = fs.readFileSync(packageYmlPath, 'utf8');
    packageYml = packageYml.replace(/version:\s*['"]?[^'"]*['"]?/g, `version: '${version}'`);
    fs.writeFileSync(packageYmlPath, packageYml);
    console.log(`✓ Updated ${packageYmlPath}`);

    // Update package.json
    const packageJsonPath = 'package.json';
    const packageJson = JSON.parse(fs.readFileSync(packageJsonPath, 'utf8'));
    packageJson.version = version;
    fs.writeFileSync(packageJsonPath, JSON.stringify(packageJson, null, 2) + '\n');
    console.log(`✓ Updated ${packageJsonPath}`);

    // Find and update PHP files with version constants
    const phpFiles = ['boot.php'];
    for (const file of phpFiles) {
        if (fs.existsSync(file)) {
            let content = fs.readFileSync(file, 'utf8');
            // Update various version patterns commonly found in PHP
            content = content.replace(/(['"])[\d.]+\1\s*\/\*\s*version\s*\*\//gi, `'${version}' /* version */`);
            content = content.replace(/const\s+VERSION\s*=\s*['"][^'"]*['"]/gi, `const VERSION = '${version}'`);
            fs.writeFileSync(file, content);
            console.log(`✓ Updated ${file}`);
        }
    }

    console.log(`\n🎉 Version updated to ${version} successfully!`);
    
} catch (error) {
    console.error('❌ Error updating version:', error.message);
    process.exit(1);
}