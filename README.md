# LaravelAnalyzer

A lightweight tool to scan a Laravel application and retrieve detailed, structured information about your laravel project for complex tasks.

Usage is super simple:

```php
use quintenmbusiness\LaravelAnalyzer\LaravelAnalyzer;

$models = (new LaravelAnalyzer())->getModels();
```

---

## Methods (inline expandable outputs)

<div style="display:inline-flex">
<span style="font-weight:bold; margin-right:10px;">getModels()</span>

<details style="display:inline-block;">
<summary style="
    cursor:pointer; 
    background-color:#4CAF50; 
    color:white; 
    padding:4px 10px; 
    border-radius:5px; 
    font-size:0.9em;
    display:inline-block;
">
Click to expand
</summary>

```php
Collection [
    ModelObject {
        name: "User",
        path: "App\\Models\\User",
        table: TableObject {
            name: "users",
            columns: [
                ColumnObject { name: "id", type: "number", rawType: "bigint(20) unsigned", nullable: false, default: null },
                ColumnObject { name: "name", type: "string", rawType: "varchar(255)", nullable: false, default: null },
                ColumnObject { name: "email", type: "string", rawType: "varchar(255)", nullable: false, default: null },
                ColumnObject { name: "email_verified_at", type: "datetime", rawType: "timestamp NULL", nullable: true, default: null },
                ColumnObject { name: "password", type: "string", rawType: "varchar(255)", nullable: false, default: null },
                ColumnObject { name: "remember_token", type: "string", rawType: "varchar(100)", nullable: true, default: null },
                ColumnObject { name: "created_at", type: "datetime", rawType: "timestamp NULL", nullable: true, default: null },
                ColumnObject { name: "updated_at", type: "datetime", rawType: "timestamp NULL", nullable: true, default: null },
            ]
        },
        relations: [
            ModelRelationObject { method: "notifications", returns: "MorphMany", relatedTable: "Illuminate\\Notifications\\DatabaseNotification" },
        ]
    }
]
```

</details>

</div>
