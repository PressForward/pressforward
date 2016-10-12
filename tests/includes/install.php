<?php

/**
 * PressForward needs a little setup to be fully functional
 */

// Install the Relationships table
pressforward('schema.relationships')->install_relationship_table();
