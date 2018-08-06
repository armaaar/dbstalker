<?php

/**
 * Singleton Pattern.
 *
 * Modern implementation.
 */
class Stalker_Singleton
{
    /**
     * Call this method to get singleton
     */
    public static function instance()
    {
      static $instance = null;
      if( $instance === null )
      {
        // Late static binding (PHP 5.3+)
        $instance = new static();
      }

      return $instance;
    }

    /**
     * Make constructor protected, so nobody can call "new Class" but children.
     */
    protected function __construct() {}

    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone() {}

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep() {}

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup() {}

}
?>
