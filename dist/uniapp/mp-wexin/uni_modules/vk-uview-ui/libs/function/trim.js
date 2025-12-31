"use strict";exports.trim=function(e,r="both"){return"both"==r?e.replace(/^\s+|\s+$/g,""):"left"==r?e.replace(/^\s*/,""):"right"==r?e.replace(/(\s*$)/g,""):"all"==r?e.replace(/\s+/g,""):e};
