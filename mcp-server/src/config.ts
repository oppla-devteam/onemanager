export const config = {
  apiUrl: process.env.ONEMANAGER_API_URL || "http://localhost:8000/api",
  authToken: process.env.ONEMANAGER_AUTH_TOKEN || "",
  // Bink OAuth
  binkClientId: process.env.BINK_CLIENT_ID || "",
  binkClientSecret: process.env.BINK_CLIENT_SECRET || "",
  // HTTP server
  mcpServerUrl: process.env.MCP_SERVER_URL || "http://localhost:3100",
  mcpPort: parseInt(process.env.MCP_PORT || "3100", 10),
};
