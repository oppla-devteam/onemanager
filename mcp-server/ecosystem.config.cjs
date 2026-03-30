module.exports = {
  apps: [
    {
      name: "onemanager-mcp",
      script: "dist/http-server.js",
      cwd: "/var/www/onemanager/mcp-server",
      env: {
        NODE_ENV: "production",
        MCP_PORT: 3100,
        MCP_SERVER_URL: "https://pedro.oppla.club",
        ONEMANAGER_API_URL: "https://pedro.oppla.club/api",
        BINK_CLIENT_ID: "onemanager-b696eb",
        BINK_CLIENT_SECRET: "bink_be543a838d8fc94bb95b5dc099c539a620c7adc2ea29fe0b3fc1120e7c9ee66b",
      },
      instances: 1,
      autorestart: true,
      max_memory_restart: "256M",
      log_date_format: "YYYY-MM-DD HH:mm:ss",
      error_file: "/var/log/pm2/onemanager-mcp-error.log",
      out_file: "/var/log/pm2/onemanager-mcp-out.log",
    },
  ],
};
