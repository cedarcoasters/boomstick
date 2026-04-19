# BoomStick Documentation

Official documentation site for the **BoomStick PHP MVC Framework** - a lightweight framework for rapid web application development.

**Live Site:** [https://primitivescrewheads.dev](https://primitivescrewheads.dev)

## Overview

This documentation site provides comprehensive guides for getting started with BoomStick and building web applications using its modular architecture.

### Getting Started

The landing page walks you through the complete setup process:

1. **Clone the Repository** - Get the BoomStick source code
2. **Navigate to Project Directory** - Set up your working directory
3. **Explore the Module Generator** - Learn about the `make-module` script
4. **Create Your Entry Point Module** - Generate your first module
5. **Configure NginX** - Point the web server to your module
6. **Build and Run with Docker** - Start the development environment
7. **View Your Application** - Access the running application
8. **Install Development Tools** - Set up the Node.js module for frontend assets
9. **Build Vendor Assets** - Compile CSS and JavaScript dependencies
10. **Install PHP Composer Module** - Add PHP dependency management
11. **Initialize Composer Autoloader** - Configure autoloading

### Documentation Pages

- **Getting Started** - Step-by-step setup guide
- **Entry Module** - Working with entry modules, controllers, views, and routes
- **Core Library** - Reference documentation for all core framework libraries

### Project Structure

```
BoomStick/
├── bin/                    # Executable scripts (make-module, etc.)
├── docker-config/          # Docker configuration files
├── init/                   # Initialization scripts
├── lib/                    # Core framework libraries
├── module/                 # Application modules
│   ├── composer-*/         # PHP Composer dependency modules
│   ├── entry-*/            # Entry point modules
│   └── nodejs-*/           # Node.js build tool modules
├── template/               # Module templates for generator
└── docker-compose.yml      # Docker Compose configuration
```

## Development

This documentation site is itself built using BoomStick, serving as both documentation and a reference implementation.

### Running Locally

```bash
docker compose up --build
```

Then navigate to [http://localhost:8000/](http://localhost:8000/)

## License

MIT License - See [LICENSE](LICENSE) for details.
