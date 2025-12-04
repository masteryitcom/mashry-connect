// src/controllers/dataController.js
import fs from "fs";
import path from "path";
import { 
    readDataFiles, 
    parseDataFile, 
    extractDataFromFile 
} from "../utils/fileHelpers.js";
import { 
    getAllDataByType, 
    getAllDataFromAllFiles 
} from "../utils/dataHelpers.js";

export const getLatestData = (req, res) => {
    try {
        const type = req.params.type.toLowerCase();
        
        const allowedTypes = ["users", "products", "categories"];
        if (!allowedTypes.includes(type)) {
            return res.status(400).json({
                success: false,
                error: "Invalid type",
                message: `Type must be one of: ${allowedTypes.join(", ")}`,
                received_type: type
            });
        }
        
        const files = readDataFiles(type);
        
        if (files.length === 0) {
            return res.json({
                success: true,
                type: type,
                message: "No data files found",
                item_count: 0,
                file_count: 0,
                data: [],
                timestamp: new Date().toISOString()
            });
        }
        
        const latestFile = files[0];
        const fileData = parseDataFile(latestFile.path);
        const data = extractDataFromFile(fileData);
        
        res.json({
            success: true,
            type: type,
            source: "latest_file",
            item_count: data.length,
            file_count: files.length,
            latest_file: latestFile.filename,
            data: data,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error(`Error in /data/${req.params.type}:`, error);
        res.status(500).json({
            success: false,
            error: "Error fetching data",
            message: error.message
        });
    }
};

export const getAllData = (req, res) => {
    try {
        const type = req.params.type.toLowerCase();
        
        const allowedTypes = ["users", "products", "categories"];
        if (!allowedTypes.includes(type)) {
            return res.status(400).json({
                success: false,
                error: "Invalid type",
                message: `Type must be one of: ${allowedTypes.join(", ")}`,
                received_type: type
            });
        }
        
        const files = readDataFiles(type);
        
        if (files.length === 0) {
            return res.json({
                success: true,
                type: type,
                message: "No data files found",
                item_count: 0,
                file_count: 0,
                data: [],
                timestamp: new Date().toISOString()
            });
        }
        
        const allData = getAllDataFromAllFiles(type);
        
        res.json({
            success: true,
            type: type,
            source: "all_files",
            item_count: allData.length,
            file_count: files.length,
            files: files.map(f => ({
                filename: f.filename,
                modified: f.modified
            })),
            data: allData,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error(`Error in /data/${req.params.type}/all:`, error);
        res.status(500).json({
            success: false,
            error: "Error fetching all data",
            message: error.message
        });
    }
};

export const getFileData = (req, res) => {
    try {
        const { type, filename } = req.params;
        
        const filePath = path.join("./data", type, filename);
        
        if (!fs.existsSync(filePath)) {
            return res.status(404).json({
                success: false,
                error: "File not found",
                type: type,
                filename: filename,
                path: filePath
            });
        }
        
        const fileData = parseDataFile(filePath);
        
        if (!fileData) {
            return res.status(500).json({
                success: false,
                error: "Error parsing file",
                type: type,
                filename: filename
            });
        }
        
        const data = extractDataFromFile(fileData);
        
        res.json({
            success: true,
            type: type,
            filename: filename,
            metadata: fileData.metadata || {},
            item_count: data.length,
            original_format: fileData.data ? 'structured' : 'raw',
            data: data,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error(`Error in /data/${req.params.type}/file/${req.params.filename}:`, error);
        res.status(500).json({
            success: false,
            error: "Error fetching file data",
            message: error.message
        });
    }
};

export const searchData = (req, res) => {
    try {
        const type = req.params.type.toLowerCase();
        const searchTerm = req.query.q;
        const field = req.query.field || 'name';
        const limit = parseInt(req.query.limit) || 50;
        
        if (!searchTerm) {
            return res.status(400).json({
                success: false,
                error: "Search term required",
                message: "Please provide a search term using ?q=searchterm"
            });
        }
        
        const data = getAllDataByType(type);
        
        const searchResults = data.filter(item => {
            if (typeof item === 'object' && item !== null) {
                let value;
                
                if (field.includes('.')) {
                    const fields = field.split('.');
                    value = item;
                    for (const f of fields) {
                        if (value && typeof value === 'object') {
                            value = value[f];
                        } else {
                            value = undefined;
                            break;
                        }
                    }
                } else {
                    value = item[field];
                }
                
                if (value && typeof value === 'string') {
                    return value.toLowerCase().includes(searchTerm.toLowerCase());
                }
                
                if (value && typeof value === 'number') {
                    return value.toString().includes(searchTerm);
                }
            }
            return false;
        }).slice(0, limit);
        
        res.json({
            success: true,
            type: type,
            search_term: searchTerm,
            search_field: field,
            results_count: searchResults.length,
            total_count: data.length,
            results: searchResults,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error(`Error in /data/${req.params.type}/search:`, error);
        res.status(500).json({
            success: false,
            error: "Error searching data",
            message: error.message
        });
    }
};

export const getDataSummary = (req, res) => {
    try {
        const type = req.params.type.toLowerCase();
        
        const files = readDataFiles(type);
        const data = getAllDataByType(type);
        
        let summary = {
            type: type,
            file_count: files.length,
            total_items: data.length,
            latest_file: files.length > 0 ? files[0].filename : null,
            latest_import: files.length > 0 ? files[0].modified : null
        };
        
        if (data.length > 0) {
            if (type === 'users') {
                const roles = {};
                data.forEach(user => {
                    if (user && typeof user === 'object') {
                        const role = user.role || user.user_role || user.role_name;
                        if (role) {
                            roles[role] = (roles[role] || 0) + 1;
                        }
                    }
                });
                if (Object.keys(roles).length > 0) {
                    summary.user_roles = roles;
                }
            }
            
            if (type === 'products') {
                const categories = {};
                const statuses = {};
                data.forEach(product => {
                    if (product && typeof product === 'object') {
                        const category = product.category || product.product_category || product.category_name;
                        if (category) {
                            categories[category] = (categories[category] || 0) + 1;
                        }
                        
                        const status = product.status || product.product_status || product.status_name;
                        if (status) {
                            statuses[status] = (statuses[status] || 0) + 1;
                        }
                    }
                });
                if (Object.keys(categories).length > 0) {
                    summary.product_categories = categories;
                }
                if (Object.keys(statuses).length > 0) {
                    summary.product_statuses = statuses;
                }
            }
            
            if (type === 'categories') {
                const parentCategories = {};
                data.forEach(category => {
                    if (category && typeof category === 'object') {
                        const parent = category.parent || category.parent_id || category.parent_name;
                        if (parent) {
                            parentCategories[parent] = (parentCategories[parent] || 0) + 1;
                        }
                    }
                });
                if (Object.keys(parentCategories).length > 0) {
                    summary.parent_categories = parentCategories;
                }
            }
        }
        
        res.json({
            success: true,
            summary: summary,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error(`Error in /data/${req.params.type}/summary:`, error);
        res.status(500).json({
            success: false,
            error: "Error getting summary",
            message: error.message
        });
    }
};