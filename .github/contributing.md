# Contributing to Arcane

First off, thank you for considering contributing to Arcane! 

Arcane is a deliberately minimal single-file microframework. It is built on the philosophy that web development should be crafted, not configured. Because of the framework's strict architectural constraints, please review these guidelines before opening an issue or pull request.

## Core Principles

- **Zero Dependencies:** Arcane does not and will not use external packages. 
- **Keep it Small:** The core engine lives in a single file (`index.php`). Every new line of code is ruthlessly scrutinized for weight and necessity. "Less" is a deliberate discipline.
- **Native PHP:** We rely on modern PHP features rather than polyfills or heavy abstractions. 
- **Formatting:** We use modern PHP spacing (e.g., `if ()`, `elseif ()`) but strictly maintain a **2-space indentation** and an **80-column soft limit** to keep the file vertically dense and readable.

## Pull Requests

Pull requests for bug fixes, performance optimizations, and documentation updates are always welcome.

**Note on New Features:** Feature additions are heavily gated. If a feature is highly specific or adds significant weight, it likely belongs in the [Arcane Helpers](https://github.com/capachow/arcane-helpers) repository, not the core engine. 

### Development Process

1. Fork the repository and create a feature branch off `master`.
2. Ensure your code matches the existing minimalist architectural styles.
3. Submit your pull request with a concise explanation of the problem it solves.

Thank you for helping build something you are proud of. Keep it **Arcane**.
