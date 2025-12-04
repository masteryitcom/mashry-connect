export const healthCheck = (req, res) => {
    res.json({
        success: true,
        service: "Marfoof Data Receiver",
        version: "1.0.0",
        status: "running",
        timestamp: new Date().toISOString(),
        endpoints: {
            health: "GET /health",
            import: "POST /import/:type",
            test: "POST /test",
            "test-auth": "POST /test-auth",
            "get-data": "GET /data/:type",
            "get-all-data": "GET /data/:type/all",
            "get-file-data": "GET /data/:type/file/:filename",
            "search-data": "GET /data/:type/search",
            "data-summary": "GET /data/:type/summary",
            "list-files": "GET /files",
            "download-file": "GET /files/:type/:filename",
            "stats": "GET /stats"
        },
        api_key_required: true,
        note: "Use X-API-Key header or Authorization: Bearer API_KEY"
    });
};

export const detailedHealth = (req, res) => {
    const serverStatus = {
        success: true,
        server: "online",
        port: process.env.PORT || 5000,
        uptime: process.uptime(),
        memory: process.memoryUsage(),
        cors_enabled: true,
        authentication_required: true
    };
    
    res.json(serverStatus);
};

export const testEndpoint = (req, res) => {
    console.log("Test request body:", req.body);
    console.log("Test request headers:", req.headers);
    
    res.json({
        success: true,
        message: "Test successful! Server is working.",
        received_data: req.body,
        received_headers: {
            origin: req.headers.origin,
            'x-api-key': req.headers['x-api-key'] ? '***' : 'none',
            authorization: req.headers.authorization ? '***' : 'none'
        },
        timestamp: new Date().toISOString(),
        note: "This endpoint doesn't require authentication for testing"
    });
};

export const testAuthEndpoint = (req, res) => {
    res.json({
        success: true,
        message: "Authentication test successful!",
        authenticated: true,
        received: req.body,
        headers: {
            'x-api-key': req.headers['x-api-key'] ? '***' : 'none',
            authorization: req.headers.authorization ? '***' : 'none'
        }
    });
};