const errorHandler = (error, req, res, next) => {
    console.error("Unhandled error:", error);
    console.error("Error stack:", error.stack);
    
    res.status(500).json({
        success: false,
        error: "Internal server error",
        message: process.env.NODE_ENV === "development" ? error.message : "Something went wrong",
        stack: process.env.NODE_ENV === "development" ? error.stack : undefined
    });
};

export default errorHandler;