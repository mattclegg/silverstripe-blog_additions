<?php

//Object::useCustomClass('BlogTree', 'BlogTreeExtension');

Object::add_extension('BlogEntry', 'BlogEntryCustom');
DataObject::add_extension('BlogHolder', 'BlogHolderCustom');
DataObject::add_extension('BlogHolder_Controller', 'BlogHolderCustom_Controller');
Object::add_extension('RSSWidget', 'BlogHolderCustom_RSSWidget');