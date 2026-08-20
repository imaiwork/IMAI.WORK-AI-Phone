"use strict";const e=require("../common/vendor.js");exports.isReleaseVersion=function(){const{miniProgram:n}=e.wx$1.getAccountInfoSync();return"release"===n.envVersion||""!=n.version||!1};
