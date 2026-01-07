"use strict";const e=require("../enums/constantEnums.js"),t=require("./cache.js");exports.getToken=function(){return t.cache.get(e.TOKEN_KEY)};
