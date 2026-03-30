import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { createMcpServer } from "./register-tools.js";

// Stdio entry point (for Claude Code / local usage)
const server = createMcpServer();
const transport = new StdioServerTransport();
await server.connect(transport);
