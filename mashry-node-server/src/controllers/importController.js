import fs from "fs";
import path from "path";

const DATA_DIR = "./data";

export const importData = (req, res) => {
    try {
        const type = req.params.type.toLowerCase();
        
        console.log(`Starting import for: ${type}`);
        console.log(`   Items count: ${Array.isArray(req.body) ? req.body.length : 1}`);
        
        const allowedTypes = ["users", "products", "categories"];
        if (!allowedTypes.includes(type)) {
            return res.status(400).json({
                success: false,
                error: "Invalid type",
                message: `Type must be one of: ${allowedTypes.join(", ")}`,
                received_type: type
            });
        }
        
        if (!req.body) {
            return res.status(400).json({
                success: false,
                error: "No data",
                message: "Request body is empty"
            });
        }
        
        const typeDir = path.join(DATA_DIR, type);
        if (!fs.existsSync(typeDir)) {
            fs.mkdirSync(typeDir, { recursive: true });
            console.log(`Created directory: ${typeDir}`);
        }
        
        const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
        const filename = `${type}-${timestamp}.json`;
        const filePath = path.join(typeDir, filename);
        
        const dataToSave = {
            metadata: {
                type: type,
                received_at: new Date().toISOString(),
                source: "wordpress",
                filename: filename,
                item_count: Array.isArray(req.body) ? req.body.length : 1,
                total_size: JSON.stringify(req.body).length,
                headers: {
                    origin: req.headers.origin,
                    'content-type': req.headers['content-type']
                }
            },
            data: req.body
        };
        
        fs.writeFileSync(filePath, JSON.stringify(dataToSave, null, 2));
        
        console.log(`File saved: ${filename}`);
        console.log(`   Size: ${fs.statSync(filePath).size} bytes`);
        
        res.json({
            success: true,
            message: `${type} data saved successfully`,
            details: {
                type: type,
                filename: filename,
                item_count: dataToSave.metadata.item_count,
                file_size: fs.statSync(filePath).size,
                file_path: filePath,
                saved_at: dataToSave.metadata.received_at
            }
        });
        
    } catch (error) {
        console.error("Error saving data:", error);
        res.status(500).json({
            success: false,
            error: "Internal server error",
            message: error.message
        });
    }
};