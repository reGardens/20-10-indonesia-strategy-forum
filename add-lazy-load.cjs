const fs = require('fs');
const path = require('path');

const dirsToSearch = [
    'resources/js/Pages/Landing',
    'resources/js/Components/Landing'
];

function walkDir(dir, callback) {
    fs.readdirSync(dir).forEach(f => {
        let dirPath = path.join(dir, f);
        let isDirectory = fs.statSync(dirPath).isDirectory();
        isDirectory ? 
            walkDir(dirPath, callback) : callback(path.join(dir, f));
    });
}

dirsToSearch.forEach(dir => {
    if(fs.existsSync(dir)){
        walkDir(dir, function(filePath) {
            if (filePath.endsWith('.vue')) {
                let content = fs.readFileSync(filePath, 'utf8');
                // Negative lookahead to ensure we don't add it if it already exists
                const newContent = content.replace(/<img(?![^>]*loading=["']lazy["'])/g, '<img loading="lazy"');
                if (content !== newContent) {
                    fs.writeFileSync(filePath, newContent, 'utf8');
                    console.log('Updated: ' + filePath);
                }
            }
        });
    }
});
