const fs = require('fs-extra');
const path = require('path');
const archiver = require('archiver');

async function buildDist() {
    // Clean dist directory
    await fs.remove('dist');
    await fs.mkdir('dist');

    // Build assets
    require('child_process').execSync('npm run build', { stdio: 'inherit' });

    // Copy files to staging
    const staging = 'dist/staging/schocial-scheduler';
    await fs.mkdir(staging, { recursive: true });

    const filesToCopy = [
        'build',
        'includes',
        'languages',
        'assets',
        'readme.txt',
        'LICENSE.txt',
        'schocial-scheduler.php'
    ];

    for (const file of filesToCopy) {
        await fs.copy(file, path.join(staging, file));
    }

    // Create zip
    const output = fs.createWriteStream('dist/schocial-scheduler.zip');
    const archive = archiver('zip');

    archive.pipe(output);
    archive.directory(staging, false);
    await archive.finalize();

    // Cleanup staging
    await fs.remove('dist/staging');
}

buildDist().catch(console.error);