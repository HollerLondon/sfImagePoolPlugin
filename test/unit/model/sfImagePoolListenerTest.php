<?php
require_once dirname(__FILE__).'/../../bootstrap/bootstrap.php';

$t = new lime_test(6, new lime_output_color());

// Create a new random images to work with
$images = array();

for($i = 0; $i < 5; $i++)
{
  $im = new sfImagePoolImage;
  $im->setFilename(uniqid());
  $im->setOriginalFilename(uniqid());
  $images[] = $im;
}

// Create a new image-poolable object
$obj = new ImageArticle;


$obj->setImages($images);

$t->is(count($obj->getPoolImages()->getInsertDiff()),5,"5 images flagged for insert");
$t->is(count($obj->getPoolImages()->getDeleteDiff()),0,"No images flagged to delete");
$obj->save();

$t->is(count($obj->getPoolImages()->getInsertDiff()),0,"No images flagged for insert");
$t->is(count($obj->getPoolImages()->getDeleteDiff()),0,"No images flagged to delete");

$im = new sfImagePoolImage;
$im->setFilename(uniqid());
$im->setOriginalFilename(uniqid());
$obj->getPoolImages()->add($im);

$t->is(count($obj->getPoolImages()->getInsertDiff()),1,"One new image to insert");
$obj->save();

$obj->getPoolImages()->remove(1);
$t->is(count($obj->getPoolImages()->getDeleteDiff()),1,"One old image to delete");
$obj->save();

// Clean up
$obj->delete();
foreach($images as $im)
{
  $im->delete();
}