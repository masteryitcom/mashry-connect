export const requestLogger = (req, res, next) => {
    console.log(`[${new Date().toLocaleTimeString()}] ${req.method} ${req.url}`);
    console.log(`   Origin: ${req.headers.origin || 'none'}`);
    console.log(`   X-API-Key: ${req.headers['x-api-key'] ? '***' + req.headers['x-api-key'].slice(-4) : 'none'}`);
    console.log(`   Authorization: ${req.headers['authorization'] ? '***' + req.headers['authorization'].slice(-10) : 'none'}`);
    next();
};