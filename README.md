# PHPTools

**PHPTools** is a collection of useful PHP classes and tools I have gathered over time.  
The primary focus of this repository is to provide reusable utility components, mainly designed for use within **Symfony Framework** projects.

This repository is public and used both as a utility library and as an example of PHP tooling and structural design.

---

## 📌 Overview

The project contains several directories with standalone classes or utility modules, each representing a small tool or helper:

- **Csv** – utilities for CSV handling  
- **Doctrine** – tools related to Doctrine ORM  
- **Mnb** – miscellaneous namespace bundle  
- **Other** – various helper classes  
- **Response** – response utilities  
- **SerializerHandler** – classes for handling serialization logic  
- **SomeRelatedEntitiesToListedFiles** – example or related entity utilities  
- **Validator** – validation helpers  
- **cURL** – cURL related utilities  

Each folder contains PHP source files relevant to a specific purpose or category.

---

## 🛠 Purpose

This repository is designed to:

- House reusable PHP utility classes  
- Support Symfony framework projects with common backend needs  
- Serve as a reference for structured, modular PHP tooling  
- Demonstrate practical PHP code organization and reusable components  

It is **not a single deployable application**, but rather a toolkit of components which can be integrated into other PHP projects as needed.

---

## 📂 Structure
PHPTools/
├── Csv/
├── Doctrine/
├── Mnb/
├── Other/
├── Response/
├── SerializerHandler/
├── SomeRelatedEntitiesToListedFiles/
├── Validator/
├── cURL/
└── README.md


Each subdirectory contains PHP classes grouped by functionality or use-case.

---

## 📋 Examples of Tools

- **CSV helpers** – tools to read, parse, and manipulate CSV files  
- **cURL wrappers** – reusable utilities to perform HTTP requests  
- **Validator classes** – reusable form or data validators  
- **Serializer handlers** – classes built to assist with object serialization  
- **Doctrine helpers** – Doctrine-related utility classes

These modules aim to reduce boilerplate and support common patterns in Symfony-based applications.

---

## 🚀 Usage

Since this repository is a collection of PHP classes:

1. Clone or include it in your project
2. Use an autoloader (e.g., Composer) to load the namespaces
3. Integrate the specific tool you need into your codebase

Example with Composer autoload:

```json
{
  "autoload": {
    "psr-4": {
      "PHPTools\\": "src/"
    }
  }
}
```

Then run:
```bash
composer dump-autoload
```

Now you can import any tool in your PHP code:
```php
use PHPTools\Csv\CsvHelper;
```

Adapt the class names and namespaces to match the actual structure.

---

## 📌 Context

This repository represents a personal utility library built over time and refined through use in Symfony projects.
It is intended both as a toolbox for real development needs and as a portfolio example of PHP code organization and utility engineering.

---

## 🟢 License

This project is licensed under the MIT License.
Refer to the LICENSE file for full terms.

---

## 🛠 Contributions

Community contributions are welcome. If you find a bug, a missing utility, or want to contribute a useful tool, feel free to open a pull request.

---

## 📍 Notes
- The repository currently has no published releases and contains a small number of commits.
- Code files vary in scope and may include experimental or project-specific utilities.
- Consider extracting and adapting only the tools relevant to your use case.