// src/utils/dataHelpers.js
import { readDataFiles, parseDataFile, extractDataFromFile } from "./fileHelpers.js";

const getAllDataByType = (type) => {
    try {
        const files = readDataFiles(type);
        if (files.length === 0) {
            return [];
        }
        
        const latestFile = files[0];
        const fileData = parseDataFile(latestFile.path);
        
        if (!fileData) {
            return [];
        }
        
        return extractDataFromFile(fileData);
    } catch (error) {
        console.error(`Error getting data for ${type}:`, error);
        return [];
    }
};

const getAllDataFromAllFiles = (type) => {
    try {
        const files = readDataFiles(type);
        if (files.length === 0) {
            return [];
        }
        
        let allData = [];
        
        files.forEach(file => {
            const fileData = parseDataFile(file.path);
            const extractedData = extractDataFromFile(fileData);
            allData = allData.concat(extractedData);
        });
        
        return allData;
    } catch (error) {
        console.error(`Error getting all data for ${type}:`, error);
        return [];
    }
};

// Export all functions
export {
    getAllDataByType,
    getAllDataFromAllFiles
};