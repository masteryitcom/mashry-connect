// src/utils/fileHelpers.js
import fs from "fs";
import path from "path";

const DATA_DIR = "./data";

export const readDataFiles = (type) => {
    try {
        const typeDir = path.join(DATA_DIR, type);
        if (!fs.existsSync(typeDir)) {
            return [];
        }
        
        const files = fs.readdirSync(typeDir)
            .filter(file => file.endsWith('.json'))
            .map(file => ({
                filename: file,
                path: path.join(typeDir, file),
                modified: fs.statSync(path.join(typeDir, file)).mtime
            }))
            .sort((a, b) => new Date(b.modified) - new Date(a.modified));
        
        return files;
    } catch (error) {
        console.error(`Error reading files for ${type}:`, error);
        return [];
    }
};

export const parseDataFile = (filePath) => {
    try {
        const fileContent = fs.readFileSync(filePath, 'utf8');
        const data = JSON.parse(fileContent);
        return data;
    } catch (error) {
        console.error(`Error parsing file ${filePath}:`, error);
        return null;
    }
};

export const extractDataFromFile = (fileData) => {
    if (!fileData) {
        return [];
    }
    
    if (fileData.data) {
        return Array.isArray(fileData.data) ? fileData.data : [fileData.data];
    } else if (Array.isArray(fileData)) {
        return fileData;
    } else {
        return [fileData];
    }
};