---
currentMenu: axolotl
---
# Axolotl: Working with the Database

When writing a plugin or application on top of WordPress, you'll invariably need to persist data to the database. `Jaxion`'s `Axolotl` package will be able handle all of the database interactions, abstracting over WordPress's underlying `WP_Post`-like objects and their associated WordPress `meta` or custom table. `Axolotl\Model` handles this abstraction, allowing the application to interact with the data without worrying about where that data gets persisted.

## Declaring a Data Model

To abstract over the WordPress data types, the application needs to extend the built-in `Axolotl\Model` class. The `Model` uses `Contract`s to determine how the data is persisted and a few properties to define how the model attributes are updated and serialized.

### Declaring Model Attributes

There are 2 properties which define the Model's attributes:

* `$fillable`: Defines attributes that can be set, regardless of whether the Model is currently `guarded`.
* `$guarded`: Defines attributes that can only be set when the Model is **not** `guarded`.

Both `$fillable` & `$guarded` can be used defined; together, they make up the Model's attributes. It is recommended to define the Model's attributes in its docblock:

```php
<?php

/**
 * @property int $ID
 */
class MyClass extends Model {
	protected $fillable = array(
		'ID',
	);
}
```

Attributes can be set and retrieved using `{get/set}_attribute`:

```php
<?php
$model = new MyClass();
$model->set_attribute( 'ID', 1 );
$model->get_attribute( 'ID' ); // 1
```

#### Computed Attributes

In addition to the attributes defined in `$fillable` & `$guarded`, `Axolotl\Model` supports computed properties. In order to define a computed property, define a method that begins with `compute_`:

```php
<?php

/**
 * @property int $ID
 */
class MyClass extends Model {
	protected $fillable = array(
		'ID',
	);

	public function compute_url() {
		return 'http://example.com/?id=' . $this->get_attribute( 'ID' );
	}
}
```

The computed value can be retrieved through `get_attribute`. For computed properties, `set_attribute` throws an `Exception`:

```php
<?php
$model = new MyClass();
$model->get_attribute( 'url' ); // 'http://example.com/?id=1'
$model->set_attribute( 'url', 'http://example.com/?id=3' ); // PropertyDoesNotExistException
```

### Data Source Contracts

A `Contract` **must** be implemented in order for that `Model` to work; an `Exception` will be thrown if you attempt to instantiate a `Model` without a proper `Contract`.

`Jaxion` currently ships with these data source `Contracts`:

* WordPress Object `Contracts`
  * `Contract\Axolotl\UsesWordPressPost`
  * `Contract\Axolotl\UsesWordPressTerm`
* Custom Table `Contract`
  * `Contract\Axolotl\UsesCustomTable`

Each of those `Contract`s require particular methods to be implemented, defining how the data source is backed. The WordPress Object `Contract`s require methods to define the object type, while the Custom Table `Contract` requires a method to define the table name. Only one WordPress Object `Contract` can be implemented on a single Model.

A Model implementing a WordPress Object `Contract` needs to explicitly map Model attributes to the underlying object. This is handled by defining a method beginning with `map_`:

```php
<?php

use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;

/**
 * @property int $ID
 * @property string $title
 */
class MyClass extends Model implements UsesWordPressPost {
	protected $fillable = array(
		'ID',
		'title'
	);

	public function map_title() {
		return 'post_title';
	}
}
```

This maps the Model attribute `title` to the WP_Post property `post_title`. This way, the naming of the Model isn't constrained by the naming conventions of the underlying object. When using `set_attribute`, the mapped property of the underlying object will be updated:

```php
$model = new MyClass();
$model->set_attribute( 'title', 'New Title' );
$model->get_underlying_wp_object()->post_title // 'New Title'
```

By default, a Model with an implemented WordPress Object `Contract` will use the object's `meta` to back any attributes not mapped to its object. If a Model implementing a WordPress Object `Contract` also implements the Custom Table `Contract`, the Model will use a custom table to back those attributes.

If the Model only implements the Custom Table `Contract`, all of the Model's attributes will be backed by a custom table.

### Controlling `Model::serialize`

`Model::serialize` returns an associative array of the Model's attributes, perfect for returning data for an API response. The attributes included in `serialize` are controlled by these Model properties:

* `$visible`: Defines attributes that are visible in `Model::serialize`.
* `$hidden`: Defines the attributes that are hidden from `Model::serialize`.

If both `$visible` & `$hidden` are defined, only `$visible` is used to determine the attributes returned by `Model::serialize`. If neither `$visible` nor `$hidden` are defined, all of the Model's attributes are returned, including `computed` attributes.

### Example Data Model

```php
<?php

use Intraxia\Jaxion\Axolotl\Model;
use Intraxia\Jaxion\Contract\Axolotl\UsesWordPressPost;

class MyModel extends Model implements UsesWordPressPost {
	/**
	 * These properties can be assigned by default.
	 */
	protected $fillable = array(
		'title',
		'text',
	);

	/**
	 * These properties cannot be assigned by default.
	 */
	protected $guarded = array(
		'ID',
	);

	/**
	 * These properties are serialized with `Model::serialize`.
	 */
	protected $visible = array(
		'title',
		'text',
		'url',
	);

	/**
	 * The custom post type to save the WordPress Post objects as.
	 */
	public static function get_post_type() {
		return 'custom';
	}

	/**
	 * Maps the ID attribute to the WP_Post::ID property.
	 */
	protected function map_ID() {
		return 'ID';
	}

	/**
	 * Maps the title attribute to the WP_Post::post_title property.
	 */
	protected function map_title() {
		return 'post_title';
	}

	/**
	 * Computes the url attribute.
	 */
	public function compute_url() {
		return 'http://example.com/?id=' . $this->get_attribute( 'ID' );
	}
}
```

## Interacting with the Database

Currently, this functionality has to be handled by the application code. An `EntityManager` for persisting Model's and their backing data to the database is underway.
