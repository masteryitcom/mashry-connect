import dotenv from "dotenv";
dotenv.config();

const API_KEY = process.env.API_KEY || "marfoof-default-key-12345";

export const authenticate = (req, res, next) => {
    let apiKey = req.headers['x-api-key'];
    
    if (!apiKey && req.headers['authorization']) {
        const authHeader = req.headers['authorization'];
        if (authHeader.startsWith('Bearer ')) {
            apiKey = authHeader.substring(7);
        }
    }
    
    if (!apiKey && req.query.api_key) {
        apiKey = req.query.api_key;
    }
    
    if (!apiKey) {
        console.log("No API key provided");
        return res.status(401).json({
            success: false,
            error: "API key is required",
            hint: "Add header: X-API-Key: your-key or Authorization: Bearer your-key"
        });
    }
    
    if (apiKey !== API_KEY) {
        console.log(`Invalid API key. Received: ${apiKey.substring(0, 8)}...`);
        console.log(`   Expected: ${API_KEY.substring(0, 8)}...`);
        return res.status(403).json({
            success: false,
            error: "Invalid API key",
            received_key_prefix: apiKey.substring(0, 8) + "...",
            expected_key_prefix: API_KEY.substring(0, 8) + "..."
        });
    }
    
    console.log("API key valid");
    next();
};