import { DigitalHumanModelVersionEnum, MontageStylesChooseType } from "../_enums";
// 高级版和尊享版公共上传限制
export const commonUploadLimit = {
    size: 300,
    // 最小分辨率
    minResolution: 360,
    // 最大分辨率
    maxResolution: 2000,
    // 最小时长
    videoMinDuration: 5,
    // 最大时长
    videoMaxDuration: 600,
};

// 上传限制
export const uploadLimit: Record<DigitalHumanModelVersionEnum, any> = {
    [DigitalHumanModelVersionEnum.STANDARD]: {
        size: 100,
        // 最小分辨率
        minResolution: 360,
        // 最大分辨率
        maxResolution: 2000,
        // 最小时长
        videoMinDuration: 15,
        // 最大时长
        videoMaxDuration: 60,
    },
    [DigitalHumanModelVersionEnum.SUPER]: {
        size: 500,
        // 最小分辨率
        minResolution: 640,
        // 最大分辨率
        maxResolution: 2048,
        // 最小时长
        videoMinDuration: 2,
        // 最大时长
        videoMaxDuration: 120,
    },
    [DigitalHumanModelVersionEnum.ADVANCED]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.ELITE]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.SHANJIAN]: commonUploadLimit,
    [DigitalHumanModelVersionEnum.CHANJING]: {
        size: 2000,
        minResolution: 360,
        maxResolution: 2000,
        videoMinDuration: 30,
        videoMaxDuration: 300,
    },
};

const imageAccept = [".webp", ".jpg", ".png"];
const videoAccept = [".mp4", ".mov"];
export const montageUploadConfig = {
    isTranscode: false,
    count: 9,
    imageAccept,
    videoAccept,
    imageSize: 20,
    imageResolution: [2000, 2000],
    videoSize: 200,
    videoDuration: [1, 59],
    videoResolution: [2000, 2000],
    fileAccept: [...imageAccept, ...videoAccept],
    fileSize: 100,
    imageDuration: 2,
    materialTotalDuration: 5,
};

// 数字人口播混剪
export const digitalPersonTemplates = {
    [MontageStylesChooseType.ALL]: [
        {
            name: "素简黄白",
            pic: "bdcc5298cb4bc1384942f5a1f14490fd.webp",
            templateID: "6904552d68f703003047c54f",
        },
        {
            name: "桂香白黄",
            pic: "68efda348035c286bc6199491e3aec0b.webp",
            templateID: "68d8aa8957b27500381021d9",
        },
        {
            name: "活力白蓝",
            pic: "73c431a5bafe55f33d56b00c3709e516.webp",
            templateID: "68d51957082afd003042ad0c",
        },
        {
            name: "可爱彩色",
            pic: "12341c426e692f0229ce4fd66d451a8c.webp",
            templateID: "68cd358f1bd747003331ef26",
        },
        {
            name: "静谧黄白",
            pic: "d2db025776312b0c68330635647b398f.webp",
            templateID: "68c91ddcc26e350031082ae8",
        },
        {
            name: "翻转红白",
            pic: "03cdc50fe7f6ac40038558a5bb2c6b36.webp",
            templateID: "68bab05f49c0ae002fc0caec",
        },
        {
            name: "重点红黄",
            pic: "785f4d758e03c598578221bd6a45fecf.webp",
            templateID: "68b93eb6a02ae2003036d063",
        },
        {
            name: "高级黄红",
            pic: "f9009b612fe5037a27bdb3ae6b406012.webp",
            templateID: "68b6d13accd8300033ca0c54",
        },
        {
            name: "高级白橙",
            pic: "91190e9f0b7e433630a733331954aec0.webp",
            templateID: "68b6c63f49c0ae002fbf2dec",
        },
        {
            name: "黑底双语",
            pic: "d62757fea5408cbd0189b71de66f471e.webp",
            templateID: "68b6aad9af3b800032935c7a",
        },
        {
            name: "高级黄白",
            pic: "2e57eed623d01c804857cd1f0b4c8264.webp",
            templateID: "68b589a51a8e920031ee7f8b",
        },
        {
            name: "书法双语",
            pic: "265c78f508485e5a2df3e5254f115709.webp",
            templateID: "68b56b77ccd8300033c97097",
        },
        {
            name: "通用橙黄",
            pic: "5782c2ac3637d5f8c53d985c7105956e.jpg",
            templateID: "68b1891a6028800031df742d",
        },
        {
            name: "七夕粉紫",
            pic: "50fc1e9563ed0bbc3aa277d50fac324a.jpg",
            templateID: "68b1227f5fd04a002ffef642",
        },
        {
            name: "简易橙黄",
            pic: "06e34b1551b58ee11270008ade66a9ad.jpg",
            templateID: "68afc7e18cb1b40031e28ef3",
        },
        {
            name: "黄白字幕",
            pic: "5858f2bd31d500a04d1b0ecbb2ca0a8e.webp",
            templateID: "68a704bc46591d00311c3205",
        },
        {
            name: "通用黄红",
            pic: "ad1d45ea6c98da1fd03a085fab803fff.jpg",
            templateID: "68a6f9bb46591d00311c2ab3",
        },
        {
            name: "重点红白",
            pic: "afe095990aaedba00f71b6feaf1c2726.jpg",
            templateID: "68a6d9b4550be9003978e93e",
        },
        {
            name: "商务白蓝",
            pic: "a8f9dee2bed785bd482ae178c61929bb.jpg",
            templateID: "68a5b05641e77f0037c555d7",
        },
        {
            name: "简易黄",
            pic: "dd429444e796f566ea62c361a934804e.webp",
            templateID: "68a595bfb5b5a80030c73a2d",
        },
        {
            name: "情感黄白",
            pic: "65f4d06938693b8df60ab3ec740b3b85.jpg",
            templateID: "68a54620b5b5a80030c71213",
        },
        {
            name: "重点橙红",
            pic: "ece37610bdb1bf723bd35334584c5157.webp",
            templateID: "68a4415598ee6e0031d33c63",
        },
        {
            name: "书法黄白",
            pic: "2a30e9b8206f2d37349485454894b0c9.jpg",
            templateID: "68a43427d506f200380262dc",
        },
        {
            name: "三屏白绿",
            pic: "b3382e0bc884bbe24e32d0e78aa314c5.jpg",
            templateID: "689da586b27a9900310b74fb",
        },
        {
            name: "棕色三屏",
            pic: "f55168afa70452ea6bbdbc2a289d37c3.jpg",
            templateID: "689d8c6165fbf10039b153fe",
        },
        {
            name: "深蓝三屏",
            pic: "b72a666eef412a39f5027e0848cccb8f.webp",
            templateID: "689c567f4eaadd0030c154f8",
        },
        {
            name: "情感紫黄",
            pic: "0b80dfb7157d950258b04b880cdefa29.jpg",
            templateID: "6895b582ecf75a0038bde7f1",
        },
        {
            name: "百搭蓝白",
            pic: "631f6decc11b16c07da5ecf13a6af743.webp",
            templateID: "68944784308d030032249c7f",
        },
        {
            name: "营销橙黄",
            pic: "f107abbe71e00132b21c49473828e891.webp",
            templateID: "6891cbec39bd0400384859cc",
        },
        {
            name: "经典黄白",
            pic: "2ba20c409b8c0211dbe5be9742442d1e.webp",
            templateID: "688c70e77b75e500326f1dde",
        },
        {
            name: "百搭暖黄",
            pic: "cf12f194f2bb71d8dfb8920e4be90a51.webp",
            templateID: "688c612e00d042003017e131",
        },
        {
            name: "通用粉黄",
            pic: "b617bd21afa5987910a03c4cbc60998b.webp",
            templateID: "6882fc4f4894d300312b00ac",
        },
        {
            name: "简约双语",
            pic: "517cddbc5c67f9d8dc778cdc195f4a91.jpg",
            templateID: "6879dfe03b9dea0033f9d86d",
        },
        {
            name: "纯白双语",
            pic: "1d1d12e0505978b811865fe1a0f02713.webp",
            templateID: "68786818c33a110031e3727a",
        },
        {
            name: "红白双语",
            pic: "8696c2df196c846e5da10b85d0e59e41.webp",
            templateID: "68778cd5d1a79c003d3a3fc8",
        },
        {
            name: "黄白双语",
            pic: "3342b712d2573c66fee6a232aaa4357a.webp",
            templateID: "68778be8d2d59300301ef6b4",
        },
        {
            name: "网感白橙",
            pic: "f7fc0c65b704efeb511424c84e2e1c0e.webp",
            templateID: "6876111d43c4a1003860cffe",
        },
        {
            name: "跟随字幕",
            pic: "9146829225fec231b0faf36989ef8249.webp",
            templateID: "68537d7ea6b684003174fb59",
        },
        {
            name: "简易橙黄",
            pic: "7de8a4d672ad2aa5911e08d2da061f75.jpg",
            templateID: "6853770bf4c3530030d54ea1",
        },
        {
            name: "常规黄白",
            pic: "b4b6fe96cf42ab743069b8f33d00d8d6.webp",
            templateID: "685126890c508d0030262c9f",
        },
        {
            name: "简约黄白",
            pic: "c9f0a8de13ecd07d11724d068cc7f2d6.jpg",
            templateID: "684bec5800eac800384deb7f",
        },
        {
            name: "综艺绝绝紫",
            pic: "d3011c4bc60f28480a25a93118ca83a4.png",
            templateID: "684bd620d7c4ca003251f4c5",
        },
        {
            name: "红蓝科普",
            pic: "5a0615ab81d50c8c199fe1233a0bebe0.webp",
            templateID: "68496071f4c3530030d42bcd",
        },
        {
            name: "棕黄简约",
            pic: "b3a8626ea56a37a70dfe2d43db0af120.jpg",
            templateID: "68492ca30c508d0030254a74",
        },
        {
            name: "活力粉白",
            pic: "36c0bb2a7c0941b328c579c862c85f85.jpg",
            templateID: "6848054b00eac800384d7313",
        },
        {
            name: "黄橙商务",
            pic: "279aec55057b6ecaaaee4546a834b2b7.webp",
            templateID: "6847da4764fc05003246ce94",
        },
        {
            name: "粉白吸睛",
            pic: "3c5618752a0c0258ef21273baf6c08e4.jpg",
            templateID: "6847d5d1f4c3530030d3f753",
        },
        {
            name: "醒目橙黄",
            pic: "5480eb1594ff405727f9da14ceca20e0.jpg",
            templateID: "6846ada8b3d336003003a657",
        },
        {
            name: "黑白聚焦",
            pic: "f4e76537288ec7b396d4c76a65638930.png",
            templateID: "6842b43414aa6c003083ab41",
        },
        {
            name: "白红渐变",
            pic: "48c4ae112d6711af6c80997d46dbb16e.webp",
            templateID: "68395e5c7c2fc5003012eb39",
        },
        {
            name: "粉白渐变",
            pic: "d3b7de6d8a4fabad34d5d834001d0113.webp",
            templateID: "68384590cbab40003009bf3c",
        },
        {
            name: "高级橙白",
            pic: "bf895862c3850c2747794bfd6303b623.webp",
            templateID: "683819b9cd336f005cb7f813",
        },
        {
            name: "活泼黄蓝",
            pic: "28c37157d1e7cccf71802f6db160ce5d.webp",
            templateID: "6838069ccbab400030093f54",
        },
        {
            name: "基础黄白",
            pic: "af5813efbffbe6620d0731454000972c.webp",
            templateID: "6837cf4ccbab40003008b871",
        },
        {
            name: "商务蓝白",
            pic: "cc2c6d45a607f61c06ff62d4bcce31df.webp",
            templateID: "6836d1554275200065ccf969",
        },
        {
            name: "黄白字裂开",
            pic: "21ed1081c61a88020c0e6728fa52d7bf.webp",
            templateID: "6836ca78cbab4000300668ef",
        },
        {
            name: "极简黄白",
            pic: "ca9c6c64810f173aef10879f7c3cf70c.jpg",
            templateID: "6836b4f1cbab400030062e8b",
        },
        {
            name: "硬核紫",
            pic: "d11f80d0f0d32f561b320654a0fe65c0.webp",
            templateID: "68356ac75e3ec40066a33875",
        },
        {
            name: "棕黄风格",
            pic: "9103b7dd7aa271eb23e9cd49d9741441.png",
            templateID: "68344a5fcd336f005cb7b4d3",
        },
        {
            name: "淡雅黑白",
            pic: "311896eabcb0ece95c6549d241c75925.png",
            templateID: "68343a063f03bb005eec97f4",
        },
        {
            name: "简单黄白",
            pic: "d37d862daa2778c846c759f8b2f3555b.jpg",
            templateID: "68342c3e4275200065cccc90",
        },
        {
            name: "综艺渐变",
            pic: "adf1e29027b4b335c0ea6dcb277bf758.png",
            templateID: "6833ff3cbe66110038f515bf",
        },
        {
            name: "文艺黄白",
            pic: "a1a561effec5cb9b9b76a055053f7f57.webp",
            templateID: "68302bd68a14430033a60a68",
        },
        {
            name: "白橙风格",
            pic: "da962ccc6c4af86bdc1edabcb4d965fb.webp",
            templateID: "682ff0f7691efe00316e2807",
        },
        {
            name: "清新端午",
            pic: "0c9b48ce20fbdd6422073959885835c4.webp",
            templateID: "682ee8747a7bde0030479d7d",
        },
        {
            name: "简洁黄白",
            pic: "a9c2f945379b1d5698d75d43f6348eca.png",
            templateID: "682ee0b6ad487700387a8d97",
        },
        {
            name: "实用橙黄",
            pic: "fadd6ad2787e8fc92bf09d1538f60371.png",
            templateID: "682ee05995206500312cffbd",
        },
        {
            name: "基础橙白",
            pic: "9acb4a65cb0f3e696069bbdd20e31b8a.png",
            templateID: "682edf4681fc800038acf69c",
        },
        {
            name: "简易黄白",
            pic: "caec00e4bb90d015cb0e62f9b69320ad.webp",
            templateID: "682da35895206500312cf452",
        },
        {
            name: "红蓝标题",
            pic: "79ee4c6ec41d48b45fbd96bd949f503e.png",
            templateID: "682d4baa01d7b50031341642",
        },
        {
            name: "专业蓝白",
            pic: "48d4cbff096a6d25bff07ecd7c8e8dc2.jpg",
            templateID: "682d4ab281fc800038ace423",
        },
        {
            name: "清新黄白",
            pic: "65095cf3d8589b47bc8e7ed228dba213.jpg",
            templateID: "682d29d395206500312ce9c1",
        },
        {
            name: "网感橙黄",
            pic: "da58d92bd1e8cfd0b3207b8684601ff1.jpg",
            templateID: "682c24537a7bde00304778da",
        },
        {
            name: "520浪漫粉",
            pic: "14e041a6b7f0b43c7087912fb38ea8a7.png",
            templateID: "6826df6cad487700387a142d",
        },
        {
            name: "耀眼红",
            pic: "a81c232413c0a9c488fc151d905b992d.jpg",
            templateID: "68269ae2ad487700387a0bfc",
        },
        {
            name: "通用黄白",
            pic: "f277e7418784e6644f3aa47a2656eba7.jpg",
            templateID: "681dabc7ad992c00319c6957",
        },
        {
            name: "母亲节",
            pic: "f0b8c42aaed73736a3a730aa892c0b75.png",
            templateID: "681d621a9246d60032467054",
        },
        {
            name: "诗意黄白",
            pic: "c5ad2b105fce332266fc9a89ce61b8e6.webp",
            templateID: "681c20b74ff53e003111b165",
        },
        {
            name: "通用黄橙",
            pic: "18ba09261a40a48ec9ba2ed2fa32c753.jpg",
            templateID: "681ac74c22ba3200304dd732",
        },
        {
            name: "通用营销",
            pic: "64779d63599ef926888a9de08bd6f924.jpg",
            templateID: "6819cfb6b1f55f0032433382",
        },
        {
            name: "科技黄白",
            pic: "6dc0a9df94829694ff3f8587b1dd3438.png",
            templateID: "680b455f200a98003031ba24",
        },
        {
            name: "个性黄白",
            pic: "fb619d86e1b02b81f804fbcd71591527.png",
            templateID: "6805ed331d22120030f2d60b",
        },
        {
            name: "蓝白资讯",
            pic: "96b971e421242f1fe829d916111a9e45.png",
            templateID: "6801c7e163f7bd0030ea1609",
        },
        {
            name: "清新绿",
            pic: "412fe941cbf20854c48d7b0f232a4e8d.jpg",
            templateID: "6800c928dbdf2800324721e9",
        },
        {
            name: "明亮黄",
            pic: "ac4760e027e645d88e4205d8e81d5078.png",
            templateID: "6800b6f616d86d0031c6b84e",
        },
        {
            name: "通用黄",
            pic: "f2746d09b1cd537ac4c59e16f4007a89.jpg",
            templateID: "67ff6b67af47cc00314dc09e",
        },
        {
            name: "热门资讯",
            pic: "1b5306a2f4e78114af363d9f66df7262.jpg",
            templateID: "67fe1559af47cc00314dbd9a",
        },
        {
            name: "门店营销",
            pic: "e505b1155499a2a76f173ebe29460be1.png",
            templateID: "67fe035c01c02c0032c87931",
        },
        {
            name: "蓝白调",
            pic: "bb04f84331f2f6dbe45d7f0beaf0c7c6.png",
            templateID: "67fc9fa363f7bd0030e9d1b9",
        },
        {
            name: "网感营销",
            pic: "d55190ab0559d8972b47f319e7f6b3b0.jpg",
            templateID: "67f8d8deded06400382f466b",
        },
        {
            name: "通用橙",
            pic: "a7d0a60b7708340ae460f5684b026141.webp",
            templateID: "67f8b22816d86d0031c6b04a",
        },
        {
            name: "专业黄白",
            pic: "427bac06445c2d2ea92c1074ee87a7fe.jpg",
            templateID: "67f7935502fc1b0033ea0bba",
        },
        {
            name: "百搭黄",
            pic: "5e25bc4348f2637cbbe61dff03f52316.png",
            templateID: "67f78cc763f7bd0030e99f35",
        },
        {
            name: "混搭黄",
            pic: "a82398f65e348d370463315b88cca8c3.jpg",
            templateID: "67f73fcd7be3de00308f66b2",
        },
        {
            name: "简约粉白",
            pic: "349b889061a09936b07d77b6e4acf52c.jpg",
            templateID: "67f5e7cd02fc1b0033ea00bc",
        },
        {
            name: "专业蓝",
            pic: "0a85b981536b936fed14241e7e48ddf4.png",
            templateID: "67f4e60f63f7bd0030e95db6",
        },
        {
            name: "经典黄",
            pic: "9d515c6534b0c92e4a936ee20cb15abc.jpg",
            templateID: "67f3a6ef63f7bd0030e920fa",
        },
        {
            name: "高级橙",
            pic: "37c787b1378aed020b3a249f1241afc0.jpg",
            templateID: "67f372e263f7bd0030e9002d",
        },
        {
            name: "橙色大标题",
            pic: "3d07407b988a31018128761ecf4a1335.jpg",
            templateID: "67ea56b88f63ea002f0a0a9a",
        },
        {
            name: "半透简黄",
            pic: "d434871c14c805fbc68ab312d537ae79.webp",
            templateID: "67e60fd28f63ea002fd7310d",
        },
    ],
    [MontageStylesChooseType.VARIETY]: [
        {
            name: "可爱彩色",
            pic: "12341c426e692f0229ce4fd66d451a8c.webp",
            templateID: "68cd358f1bd747003331ef26",
        },
        {
            name: "翻转红白",
            pic: "03cdc50fe7f6ac40038558a5bb2c6b36.webp",
            templateID: "68bab05f49c0ae002fc0caec",
        },
        {
            name: "重点红黄",
            pic: "785f4d758e03c598578221bd6a45fecf.webp",
            templateID: "68b93eb6a02ae2003036d063",
        },
        {
            name: "通用橙黄",
            pic: "5782c2ac3637d5f8c53d985c7105956e.jpg",
            templateID: "68b1891a6028800031df742d",
        },
        {
            name: "情感紫黄",
            pic: "0b80dfb7157d950258b04b880cdefa29.jpg",
            templateID: "6895b582ecf75a0038bde7f1",
        },
        {
            name: "百搭暖黄",
            pic: "cf12f194f2bb71d8dfb8920e4be90a51.webp",
            templateID: "688c612e00d042003017e131",
        },
        {
            name: "通用粉黄",
            pic: "b617bd21afa5987910a03c4cbc60998b.webp",
            templateID: "6882fc4f4894d300312b00ac",
        },
        {
            name: "跟随字幕",
            pic: "9146829225fec231b0faf36989ef8249.webp",
            templateID: "68537d7ea6b684003174fb59",
        },
        {
            name: "活力粉白",
            pic: "36c0bb2a7c0941b328c579c862c85f85.jpg",
            templateID: "6848054b00eac800384d7313",
        },
        {
            name: "醒目橙黄",
            pic: "5480eb1594ff405727f9da14ceca20e0.jpg",
            templateID: "6846ada8b3d336003003a657",
        },
        {
            name: "黑白聚焦",
            pic: "f4e76537288ec7b396d4c76a65638930.png",
            templateID: "6842b43414aa6c003083ab41",
        },
        {
            name: "白红渐变",
            pic: "48c4ae112d6711af6c80997d46dbb16e.webp",
            templateID: "68395e5c7c2fc5003012eb39",
        },
        {
            name: "活泼黄蓝",
            pic: "28c37157d1e7cccf71802f6db160ce5d.webp",
            templateID: "6838069ccbab400030093f54",
        },
        {
            name: "综艺渐变",
            pic: "adf1e29027b4b335c0ea6dcb277bf758.png",
            templateID: "6833ff3cbe66110038f515bf",
        },
        {
            name: "实用橙黄",
            pic: "fadd6ad2787e8fc92bf09d1538f60371.png",
            templateID: "682ee05995206500312cffbd",
        },
    ],
    [MontageStylesChooseType.SIMPLE]: [
        {
            name: "静谧黄白",
            pic: "d2db025776312b0c68330635647b398f.webp",
            templateID: "68c91ddcc26e350031082ae8",
        },
        {
            name: "书法双语",
            pic: "265c78f508485e5a2df3e5254f115709.webp",
            templateID: "68b56b77ccd8300033c97097",
        },
        {
            name: "情感黄白",
            pic: "65f4d06938693b8df60ab3ec740b3b85.jpg",
            templateID: "68a54620b5b5a80030c71213",
        },
        {
            name: "经典黄白",
            pic: "2ba20c409b8c0211dbe5be9742442d1e.webp",
            templateID: "688c70e77b75e500326f1dde",
        },
        {
            name: "基础黄白",
            pic: "af5813efbffbe6620d0731454000972c.webp",
            templateID: "6837cf4ccbab40003008b871",
        },
        {
            name: "极简黄白",
            pic: "ca9c6c64810f173aef10879f7c3cf70c.jpg",
            templateID: "6836b4f1cbab400030062e8b",
        },
        {
            name: "棕黄风格",
            pic: "9103b7dd7aa271eb23e9cd49d9741441.png",
            templateID: "68344a5fcd336f005cb7b4d3",
        },
        {
            name: "淡雅黑白",
            pic: "311896eabcb0ece95c6549d241c75925.png",
            templateID: "68343a063f03bb005eec97f4",
        },
        {
            name: "文艺黄白",
            pic: "a1a561effec5cb9b9b76a055053f7f57.webp",
            templateID: "68302bd68a14430033a60a68",
        },
        {
            name: "简易黄白",
            pic: "caec00e4bb90d015cb0e62f9b69320ad.webp",
            templateID: "682da35895206500312cf452",
        },
        {
            name: "诗意黄白",
            pic: "c5ad2b105fce332266fc9a89ce61b8e6.webp",
            templateID: "681c20b74ff53e003111b165",
        },
        {
            name: "清新绿",
            pic: "412fe941cbf20854c48d7b0f232a4e8d.jpg",
            templateID: "6800c928dbdf2800324721e9",
        },
    ],
    [MontageStylesChooseType.HIGH]: [
        {
            name: "高级黄红",
            pic: "f9009b612fe5037a27bdb3ae6b406012.webp",
            templateID: "68b6d13accd8300033ca0c54",
        },
        {
            name: "高级白橙",
            pic: "91190e9f0b7e433630a733331954aec0.webp",
            templateID: "68b6c63f49c0ae002fbf2dec",
        },
        {
            name: "黑底双语",
            pic: "d62757fea5408cbd0189b71de66f471e.webp",
            templateID: "68b6aad9af3b800032935c7a",
        },
        {
            name: "高级黄白",
            pic: "2e57eed623d01c804857cd1f0b4c8264.webp",
            templateID: "68b589a51a8e920031ee7f8b",
        },
        {
            name: "通用黄红",
            pic: "ad1d45ea6c98da1fd03a085fab803fff.jpg",
            templateID: "68a6f9bb46591d00311c2ab3",
        },
        {
            name: "商务白蓝",
            pic: "a8f9dee2bed785bd482ae178c61929bb.jpg",
            templateID: "68a5b05641e77f0037c555d7",
        },
        {
            name: "百搭蓝白",
            pic: "631f6decc11b16c07da5ecf13a6af743.webp",
            templateID: "68944784308d030032249c7f",
        },
        {
            name: "简约双语",
            pic: "517cddbc5c67f9d8dc778cdc195f4a91.jpg",
            templateID: "6879dfe03b9dea0033f9d86d",
        },
        {
            name: "纯白双语",
            pic: "1d1d12e0505978b811865fe1a0f02713.webp",
            templateID: "68786818c33a110031e3727a",
        },
        {
            name: "红白双语",
            pic: "8696c2df196c846e5da10b85d0e59e41.webp",
            templateID: "68778cd5d1a79c003d3a3fc8",
        },
        {
            name: "黄白双语",
            pic: "3342b712d2573c66fee6a232aaa4357a.webp",
            templateID: "68778be8d2d59300301ef6b4",
        },
        {
            name: "商务蓝白",
            pic: "cc2c6d45a607f61c06ff62d4bcce31df.webp",
            templateID: "6836d1554275200065ccf969",
        },
        {
            name: "白橙风格",
            pic: "da962ccc6c4af86bdc1edabcb4d965fb.webp",
            templateID: "682ff0f7691efe00316e2807",
        },
        {
            name: "红蓝标题",
            pic: "79ee4c6ec41d48b45fbd96bd949f503e.png",
            templateID: "682d4baa01d7b50031341642",
        },
        {
            name: "专业蓝白",
            pic: "48d4cbff096a6d25bff07ecd7c8e8dc2.jpg",
            templateID: "682d4ab281fc800038ace423",
        },
        {
            name: "清新黄白",
            pic: "65095cf3d8589b47bc8e7ed228dba213.jpg",
            templateID: "682d29d395206500312ce9c1",
        },
        {
            name: "蓝白调",
            pic: "bb04f84331f2f6dbe45d7f0beaf0c7c6.png",
            templateID: "67fc9fa363f7bd0030e9d1b9",
        },
        {
            name: "百搭黄",
            pic: "5e25bc4348f2637cbbe61dff03f52316.png",
            templateID: "67f78cc763f7bd0030e99f35",
        },
        {
            name: "橙色大标题",
            pic: "3d07407b988a31018128761ecf4a1335.jpg",
            templateID: "67ea56b88f63ea002f0a0a9a",
        },
    ],
    [MontageStylesChooseType.LOCAL]: [
        {
            name: "重点红白",
            pic: "afe095990aaedba00f71b6feaf1c2726.jpg",
            templateID: "68a6d9b4550be9003978e93e",
        },
        {
            name: "简单黄白",
            pic: "d37d862daa2778c846c759f8b2f3555b.jpg",
            templateID: "68342c3e4275200065cccc90",
        },
        {
            name: "通用营销",
            pic: "64779d63599ef926888a9de08bd6f924.jpg",
            templateID: "6819cfb6b1f55f0032433382",
        },
        {
            name: "明亮黄",
            pic: "ac4760e027e645d88e4205d8e81d5078.png",
            templateID: "6800b6f616d86d0031c6b84e",
        },
        {
            name: "通用黄",
            pic: "f2746d09b1cd537ac4c59e16f4007a89.jpg",
            templateID: "67ff6b67af47cc00314dc09e",
        },
        {
            name: "门店营销",
            pic: "e505b1155499a2a76f173ebe29460be1.png",
            templateID: "67fe035c01c02c0032c87931",
        },
        {
            name: "网感营销",
            pic: "d55190ab0559d8972b47f319e7f6b3b0.jpg",
            templateID: "67f8d8deded06400382f466b",
        },
        {
            name: "简约粉白",
            pic: "349b889061a09936b07d77b6e4acf52c.jpg",
            templateID: "67f5e7cd02fc1b0033ea00bc",
        },
        {
            name: "半透简黄",
            pic: "d434871c14c805fbc68ab312d537ae79.webp",
            templateID: "67e60fd28f63ea002fd7310d",
        },
    ],
    [MontageStylesChooseType.HOT]: [
        {
            name: "常规黄白",
            pic: "b4b6fe96cf42ab743069b8f33d00d8d6.webp",
            templateID: "685126890c508d0030262c9f",
        },
        {
            name: "简约黄白",
            pic: "c9f0a8de13ecd07d11724d068cc7f2d6.jpg",
            templateID: "684bec5800eac800384deb7f",
        },
        {
            name: "红蓝科普",
            pic: "5a0615ab81d50c8c199fe1233a0bebe0.webp",
            templateID: "68496071f4c3530030d42bcd",
        },
        {
            name: "黄橙商务",
            pic: "279aec55057b6ecaaaee4546a834b2b7.webp",
            templateID: "6847da4764fc05003246ce94",
        },
        {
            name: "粉白渐变",
            pic: "d3b7de6d8a4fabad34d5d834001d0113.webp",
            templateID: "68384590cbab40003009bf3c",
        },
        {
            name: "黄白字裂开",
            pic: "21ed1081c61a88020c0e6728fa52d7bf.webp",
            templateID: "6836ca78cbab4000300668ef",
        },
        {
            name: "基础橙白",
            pic: "9acb4a65cb0f3e696069bbdd20e31b8a.png",
            templateID: "682edf4681fc800038acf69c",
        },
        {
            name: "通用黄白",
            pic: "f277e7418784e6644f3aa47a2656eba7.jpg",
            templateID: "681dabc7ad992c00319c6957",
        },
        {
            name: "专业黄白",
            pic: "427bac06445c2d2ea92c1074ee87a7fe.jpg",
            templateID: "67f7935502fc1b0033ea0bba",
        },
    ],
};

// 真人口播混剪模板数据
export const realPersonTemplates = {
    [MontageStylesChooseType.ALL]: [
        {
            name: "可爱彩色",
            pic: "2b9141614e8ef131c774783c92a9443f.jpg",
            link: "2b9141614e8ef131c774783c92a9443f.mp4",
            templateID: "68d0c61bbe5c9d0032e8a58e",
        },
        {
            name: "翻转红白",
            pic: "2a7e1a1008ce3957a20e658a4ead256f.jpg",
            link: "2a7e1a1008ce3957a20e658a4ead256f.mp4",
            templateID: "68c273827d86fd0030fd4fb5",
        },
        {
            name: "重点红黄",
            pic: "879df405639e54624bfe6d6209a237df.jpg",
            link: "879df405639e54624bfe6d6209a237df.mp4",
            templateID: "68c2734e1a8e920031f34b22",
        },
        {
            name: "高级白橙",
            pic: "24e20b87c1dd6c4090aed5193e61c4bf.jpg",
            link: "24e20b87c1dd6c4090aed5193e61c4bf.mp4",
            templateID: "68c273181a8e920031f34b02",
        },
        {
            name: "高级黄白",
            pic: "3a61e675c34f818620588bd9db5aea69.jpg",
            link: "3a61e675c34f818620588bd9db5aea69.mp4",
            templateID: "68c272cdccd8300033ce4cc4",
        },
        {
            name: "重点红白",
            pic: "3c442e0c04bd2a923590377f9e0fe095.jpg",
            link: "3c442e0c04bd2a923590377f9e0fe095.mp4",
            templateID: "68c272288dad360031c4b570",
        },
        {
            name: "情感黄白",
            pic: "131db935b128d38a04c22b1a16f09358.jpg",
            link: "131db935b128d38a04c22b1a16f09358.mp4",
            templateID: "68c271c7a02ae200303a243a",
        },
        {
            name: "棕色三屏",
            pic: "adf29b3e978c5493a889c89c2ab094a0.jpg",
            link: "adf29b3e978c5493a889c89c2ab094a0.mp4",
            templateID: "68c2716f49c0ae002fc376a5",
        },
        {
            name: "百搭蓝白",
            pic: "276b0430f4e02db5b6410b011d17629f.jpg",
            link: "276b0430f4e02db5b6410b011d17629f.mp4",
            templateID: "68c270d57d86fd0030fd4ceb",
        },
        {
            name: "百搭暖黄",
            pic: "ebdf55a4f4b3acb1dbc84cbf4695e3e2.jpg",
            link: "ebdf55a4f4b3acb1dbc84cbf4695e3e2.mp4",
            templateID: "68c2709aaf3b80003297c20e",
        },
        {
            name: "黑底双语",
            pic: "6577cebf88603af5914720a93582480b.jpg",
            link: "6577cebf88603af5914720a93582480b.mp4",
            templateID: "68c248ed8dad360031c4a1be",
        },
        {
            name: "书法双语",
            pic: "37e74be9a3213281b6dfe93b4666e340.jpg",
            link: "37e74be9a3213281b6dfe93b4666e340.mp4",
            templateID: "68c248ba1a8e920031f336a4",
        },
        {
            name: "通用黄红",
            pic: "5c2e324808777352f75ac413c5af0ec6.jpg",
            link: "5c2e324808777352f75ac413c5af0ec6.mp4",
            templateID: "68c24882ccd8300033ce3880",
        },
        {
            name: "商务白蓝",
            pic: "34c37fe57aeaed2af9e78651d50ed23c.jpg",
            link: "34c37fe57aeaed2af9e78651d50ed23c.mp4",
            templateID: "68c2482c7d86fd0030fd3a2d",
        },
        {
            name: "书法黄白",
            pic: "cc8f8c73a8cd27e847fdc42d0a5cf828.jpg",
            link: "cc8f8c73a8cd27e847fdc42d0a5cf828.mp4",
            templateID: "68c243a75549a00037805dfd",
        },
        {
            name: "三屏白绿",
            pic: "937521703984f1d0e2d1829c24b81559.jpg",
            link: "937521703984f1d0e2d1829c24b81559.mp4",
            templateID: "68c24365a02ae200303a0c25",
        },
        {
            name: "情感紫黄",
            pic: "d0ccbdf9128780c93ce71de57d0acab0.jpg",
            link: "d0ccbdf9128780c93ce71de57d0acab0.mp4",
            templateID: "68c242d98dad360031c49c9f",
        },
        {
            name: "高级黄红",
            pic: "fc3d2e00d07adb4fd821ccad19feeb95.jpg",
            link: "fc3d2e00d07adb4fd821ccad19feeb95.mp4",
            templateID: "68c242797d86fd0030fd358b",
        },
        {
            name: "营销橙黄",
            pic: "3527ac138468b7af6fe8f95c90bde33f.jpg",
            link: "3527ac138468b7af6fe8f95c90bde33f.mp4",
            templateID: "68c2423f49c0ae002fc35e56",
        },
        {
            name: "通用橙黄",
            pic: "ab2198a01a14cedb693cfa6b81d5e63b.jpg",
            link: "ab2198a01a14cedb693cfa6b81d5e63b.mp4",
            templateID: "68c2423849c0ae002fc35e4d",
        },
        {
            name: "黄白字幕",
            pic: "b2b80ed7d92a57ec7a174b836f90f682.jpg",
            link: "b2b80ed7d92a57ec7a174b836f90f682.mp4",
            templateID: "68c241c07d86fd0030fd34fe",
        },
        {
            name: "简易黄",
            pic: "14d78f8339a2c54829420618d9624f5c.jpg",
            link: "14d78f8339a2c54829420618d9624f5c.mp4",
            templateID: "68c2417faf3b80003297aa3c",
        },
        {
            name: "通用粉黄",
            pic: "5f352f98ed57a9a3698d0d46a821a313.jpg",
            link: "5f352f98ed57a9a3698d0d46a821a313.mp4",
            templateID: "68c2410f49c0ae002fc35d53",
        },
        {
            name: "重点橙红",
            pic: "eb4042907536e78b6793ee5a6006de51.jpg",
            link: "eb4042907536e78b6793ee5a6006de51.mp4",
            templateID: "68c2410da02ae200303a0aa7",
        },
        {
            name: "深蓝三屏",
            pic: "42166fbd5d756e7dafacc3b0183d4c9c.jpg",
            link: "42166fbd5d756e7dafacc3b0183d4c9c.mp4",
            templateID: "68c240cba02ae200303a0a67",
        },
        {
            name: "经典黄白",
            pic: "f3a104977edc769dd1f3cff2772f79d9.jpg",
            link: "f3a104977edc769dd1f3cff2772f79d9.mp4",
            templateID: "68c240378dad360031c49acb",
        },
        {
            name: "真人口播（动态字幕）",
            pic: "file_27.jpg",
            link: "file_27.mp4",
            templateID: "68ad2d77550be900397ad674",
        },
        {
            name: "简约双语",
            pic: "6fb4e247d15b1785a3419661745d9472.jpg",
            link: "6fb4e247d15b1785a3419661745d9472.mp4",
            templateID: "6879e391d13c7f0031567868",
        },
        {
            name: "黄白双语",
            pic: "8538f2c8fc78b45087d294f1265879f5.jpg",
            link: "8538f2c8fc78b45087d294f1265879f5.mp4",
            templateID: "6879b40dbf49c00033b0d9dc",
        },
        {
            name: "红白双语",
            pic: "484a5facb018944a15857eb100398915.jpg",
            link: "484a5facb018944a15857eb100398915.mp4",
            templateID: "6879b3e09afb52003109c4ce",
        },
        {
            name: "纯白双语",
            pic: "63ef3b270cba478061d4722563d57233.jpg",
            link: "63ef3b270cba478061d4722563d57233.mp4",
            templateID: "6879b376bf49c00033b0d9b7",
        },
        {
            name: "金融黄白",
            pic: "file_32.jpg",
            link: "file_32.mp4",
            templateID: "6858cef34e178cd82c01ec16",
        },
        {
            name: "蓝底商业",
            pic: "dd9d20d6df7cdd306eda4ff97b1e1430.jpg",
            link: "dd9d20d6df7cdd306eda4ff97b1e1430.mp4",
            templateID: "6858cef34e178cd82c01ec14",
        },
        {
            name: "通用门店",
            pic: "276587d1e37836a8e2056336e1608b6a.jpg",
            link: "276587d1e37836a8e2056336e1608b6a.mp4",
            templateID: "6858cef34e178cd82c01ec11",
        },
        {
            name: "黄白IP",
            pic: "ba756b255f8e61c22ebbc4195251085b.jpg",
            link: "ba756b255f8e61c22ebbc4195251085b.mp4",
            templateID: "6858cef34e178cd82c01ec10",
        },
        {
            name: "黄色大标题",
            pic: "file_37.jpg",
            link: "file_37.mp4",
            templateID: "6858cef34e178cd82c01ec0f",
        },
        {
            name: "半透简黄",
            pic: "682fc05764c9bcd96a340eea7235df57.jpg",
            link: "682fc05764c9bcd96a340eea7235df57.mp4",
            templateID: "6858cef34e178cd82c01ec0e",
        },
        {
            name: "橙色大标题",
            pic: "8782f00d3c3599d2012a3808d32f51e9.jpg",
            link: "8782f00d3c3599d2012a3808d32f51e9.mp4",
            templateID: "6858cef34e178cd82c01ec0d",
        },
        {
            name: "高级橙",
            pic: "3df4143976687f5bff0d08ea091a9731.jpg",
            link: "3df4143976687f5bff0d08ea091a9731.mp4",
            templateID: "6858cef34e178cd82c01ec0b",
        },
        {
            name: "经典黄",
            pic: "80ff13cfb4541ce2562a4d3bdc18c75e.jpg",
            link: "80ff13cfb4541ce2562a4d3bdc18c75e.mp4",
            templateID: "6858cef34e178cd82c01ec0a",
        },
        {
            name: "专业蓝",
            pic: "85c2681c8c79fb5eda80e9f381de5feb.jpg",
            link: "85c2681c8c79fb5eda80e9f381de5feb.mp4",
            templateID: "6858cef34e178cd82c01ec09",
        },
        {
            name: "简约粉白",
            pic: "bb723c3eec85729b08841b7199a5bd94.jpg",
            link: "bb723c3eec85729b08841b7199a5bd94.mp4",
            templateID: "6858cef34e178cd82c01ec08",
        },
        {
            name: "混搭黄",
            pic: "9d4245fae426ed443ee115eaac4435cc.jpg",
            link: "9d4245fae426ed443ee115eaac4435cc.mp4",
            templateID: "6858cef34e178cd82c01ec07",
        },
        {
            name: "百搭黄",
            pic: "58631d5cd8a56b1a74baf4c42de18e45.jpg",
            link: "58631d5cd8a56b1a74baf4c42de18e45.mp4",
            templateID: "6858cef34e178cd82c01ec05",
        },
        {
            name: "专业黄白",
            pic: "6e0e8dba7ed7de42972f719594566c89.jpg",
            link: "6e0e8dba7ed7de42972f719594566c89.mp4",
            templateID: "6858cef34e178cd82c01ec04",
        },
        {
            name: "通用橙",
            pic: "86d82fa7921a95c343be2ad6b50fe218.jpg",
            link: "86d82fa7921a95c343be2ad6b50fe218.mp4",
            templateID: "6858cef34e178cd82c01ec03",
        },
        {
            name: "网感营销",
            pic: "72a0746efae42c64b02332e8c41aae32.jpg",
            link: "72a0746efae42c64b02332e8c41aae32.mp4",
            templateID: "6858cef34e178cd82c01ec02",
        },
        {
            name: "蓝白调",
            pic: "8bf3f695c5fa7479ec19a12945568e7c.jpg",
            link: "8bf3f695c5fa7479ec19a12945568e7c.mp4",
            templateID: "6858cef34e178cd82c01ec01",
        },
        {
            name: "门店营销",
            pic: "09abc6a0a7fc14798b59150e54bccb5c.jpg",
            link: "09abc6a0a7fc14798b59150e54bccb5c.mp4",
            templateID: "6858cef34e178cd82c01ec00",
        },
        {
            name: "热门资讯",
            pic: "e073fadafd609fdcd7439985886b7978.jpg",
            link: "e073fadafd609fdcd7439985886b7978.mp4",
            templateID: "6858cef34e178cd82c01ebff",
        },
        {
            name: "通用黄",
            pic: "1f01c94fba35033409bc6bcfd9772ec3.jpg",
            link: "1f01c94fba35033409bc6bcfd9772ec3.mp4",
            templateID: "6858cef34e178cd82c01ebfd",
        },
        {
            name: "清新绿",
            pic: "c58dccc0737eda429c6697662d53c33f.jpg",
            link: "c58dccc0737eda429c6697662d53c33f.mp4",
            templateID: "6858cef34e178cd82c01ebfb",
        },
        {
            name: "蓝白资讯",
            pic: "4db7bd2aa84a64c8c4595acfbf3dcc2f.jpg",
            link: "4db7bd2aa84a64c8c4595acfbf3dcc2f.mp4",
            templateID: "6858cef34e178cd82c01ebfa",
        },
        {
            name: "个性黄白",
            pic: "be1945a69fd4508249f30d0dcb10c50c.jpg",
            link: "be1945a69fd4508249f30d0dcb10c50c.mp4",
            templateID: "6858cef34e178cd82c01ebf9",
        },
        {
            name: "科技黄白",
            pic: "459cf3af6e6e5af90679f532e95fd492.jpg",
            link: "459cf3af6e6e5af90679f532e95fd492.mp4",
            templateID: "6858cef34e178cd82c01ebf6",
        },
        {
            name: "通用营销",
            pic: "7b3c9cf05ce401e01a73f6cac71402d0.jpg",
            link: "7b3c9cf05ce401e01a73f6cac71402d0.mp4",
            templateID: "6858cef34e178cd82c01ebf2",
        },
        {
            name: "通用黄橙",
            pic: "81a72709e941302572041904358b49bf.jpg",
            link: "81a72709e941302572041904358b49bf.mp4",
            templateID: "6858cef34e178cd82c01ebf1",
        },
        {
            name: "诗意黄白",
            pic: "6ab819351e695b1a72056ad7b2a42d88.jpg",
            link: "6ab819351e695b1a72056ad7b2a42d88.mp4",
            templateID: "6858cef34e178cd82c01ebf0",
        },
        {
            name: "母亲节",
            pic: "3c18f8307eca45f940479ae92bf70917.jpg",
            link: "3c18f8307eca45f940479ae92bf70917.mp4",
            templateID: "6858cef34e178cd82c01ebef",
        },
        {
            name: "通用黄白",
            pic: "e53d65ba2c738932149167bd633ad24e.jpg",
            link: "e53d65ba2c738932149167bd633ad24e.mp4",
            templateID: "6858cef34e178cd82c01ebee",
        },
        {
            name: "耀眼红",
            pic: "243e1628dee74d46c92be65eea90aac2.jpg",
            link: "243e1628dee74d46c92be65eea90aac2.mp4",
            templateID: "6858cef34e178cd82c01ebea",
        },
        {
            name: "520浪漫粉",
            pic: "file_64.jpg",
            link: "file_64.mp4",
            templateID: "6858cef34e178cd82c01ebe9",
        },
        {
            name: "清新黄白",
            pic: "4a6f3efb23131665f3ee69861ca0b67f.jpg",
            link: "4a6f3efb23131665f3ee69861ca0b67f.mp4",
            templateID: "6858cef34e178cd82c01ebe7",
        },
        {
            name: "专业蓝白",
            pic: "b752f463b19fc38df28b5fc804991ed2.jpg",
            link: "b752f463b19fc38df28b5fc804991ed2.mp4",
            templateID: "6858cef34e178cd82c01ebe5",
        },
        {
            name: "红蓝标题",
            pic: "d56b070a45af5d923e12123c09897e47.jpg",
            link: "d56b070a45af5d923e12123c09897e47.mp4",
            templateID: "6858cef34e178cd82c01ebe4",
        },
        {
            name: "简易黄白",
            pic: "24669e1594a5a3bd8e5bc97b05f490a9.jpg",
            link: "24669e1594a5a3bd8e5bc97b05f490a9.mp4",
            templateID: "6858cef34e178cd82c01ebe3",
        },
        {
            name: "粉白风格",
            pic: "a1d646306582f6c9bf78fe063435b995.jpg",
            link: "a1d646306582f6c9bf78fe063435b995.mp4",
            templateID: "6858cef34e178cd82c01ebe2",
        },
        {
            name: "基础橙白",
            pic: "689133a2a659fe1e5eb099ae0910ee4b.jpg",
            link: "689133a2a659fe1e5eb099ae0910ee4b.mp4",
            templateID: "6858cef34e178cd82c01ebe1",
        },
        {
            name: "实用橙黄",
            pic: "2d3e12c01da161b9969f99665a756b39.jpg",
            link: "2d3e12c01da161b9969f99665a756b39.mp4",
            templateID: "6858cef34e178cd82c01ebe0",
        },
        {
            name: "简洁黄白",
            pic: "c6bb414f3f72254b2325df6549f66e4f.jpg",
            link: "c6bb414f3f72254b2325df6549f66e4f.mp4",
            templateID: "6858cef34e178cd82c01ebdf",
        },
        {
            name: "清新端午",
            pic: "9a8aa7ddf20b51413a0276eb676c4442.jpg",
            link: "9a8aa7ddf20b51413a0276eb676c4442.mp4",
            templateID: "6858cef34e178cd82c01ebde",
        },
        {
            name: "白橙风格",
            pic: "f39e9bb7898667914d82c998ee72da2a.jpg",
            link: "f39e9bb7898667914d82c998ee72da2a.mp4",
            templateID: "6858cef34e178cd82c01ebdd",
        },
        {
            name: "文艺黄白",
            pic: "6055f1ffb598b71d2e39837b32f3cf2b.jpg",
            link: "6055f1ffb598b71d2e39837b32f3cf2b.mp4",
            templateID: "6858cef34e178cd82c01ebdc",
        },
        {
            name: "综艺渐变",
            pic: "8352b8f378daddb726dd2be6921e32d0.jpg",
            link: "8352b8f378daddb726dd2be6921e32d0.mp4",
            templateID: "6858cef34e178cd82c01ebdb",
        },
        {
            name: "简单黄白",
            pic: "266c11ab9302338cc42bcebcdd1e2aab.jpg",
            link: "266c11ab9302338cc42bcebcdd1e2aab.mp4",
            templateID: "6858cef34e178cd82c01ebda",
        },
        {
            name: "淡雅黑白",
            pic: "eda30aa0aaa898f1ef0f151a1271c34f.jpg",
            link: "eda30aa0aaa898f1ef0f151a1271c34f.mp4",
            templateID: "6858cef34e178cd82c01ebd9",
        },
        {
            name: "棕黄风格",
            pic: "06ae41afda50cbf8d0f700b48fc0eaa9.jpg",
            link: "06ae41afda50cbf8d0f700b48fc0eaa9.mp4",
            templateID: "6858cef34e178cd82c01ebd8",
        },
        {
            name: "硬核紫",
            pic: "78f4d1bd7cc1a88ae8f9f4622f0491ee.jpg",
            link: "78f4d1bd7cc1a88ae8f9f4622f0491ee.mp4",
            templateID: "6858cef34e178cd82c01ebd7",
        },
        {
            name: "极简黄白",
            pic: "3c325f7fe6e147ac03ef83add8541f1a.jpg",
            link: "3c325f7fe6e147ac03ef83add8541f1a.mp4",
            templateID: "6858cef34e178cd82c01ebd6",
        },
        {
            name: "黄白字裂开",
            pic: "879130421b81c81be4e425ee15cfa059.jpg",
            link: "879130421b81c81be4e425ee15cfa059.mp4",
            templateID: "6858cef34e178cd82c01ebd5",
        },
        {
            name: "商务蓝白",
            pic: "030808c03d7af50907bfcbb3a0d4b1d6.jpg",
            link: "030808c03d7af50907bfcbb3a0d4b1d6.mp4",
            templateID: "6858cef34e178cd82c01ebd4",
        },
        {
            name: "基础黄白",
            pic: "deff44e1dfef2d5b0afa92a7a0eba05a.jpg",
            link: "deff44e1dfef2d5b0afa92a7a0eba05a.mp4",
            templateID: "6858cef34e178cd82c01ebd3",
        },
        {
            name: "活泼黄蓝",
            pic: "d4f521a5e6f0a5d1347d61c94e677e29.jpg",
            link: "d4f521a5e6f0a5d1347d61c94e677e29.mp4",
            templateID: "6858cef34e178cd82c01ebd2",
        },
        {
            name: "高级橙白",
            pic: "6b071cdf2959b7b762139cd44c1d5017.jpg",
            link: "6b071cdf2959b7b762139cd44c1d5017.mp4",
            templateID: "6858cef34e178cd82c01ebd1",
        },
        {
            name: "粉白渐变",
            pic: "42e15fb68931cb6f2b63015a2d50cb44.jpg",
            link: "42e15fb68931cb6f2b63015a2d50cb44.mp4",
            templateID: "6858cef34e178cd82c01ebd0",
        },
        {
            name: "白红渐变",
            pic: "66d97bec1f6f4aa4c1b36c434744c163.jpg",
            link: "66d97bec1f6f4aa4c1b36c434744c163.mp4",
            templateID: "6858cef34e178cd82c01ebcf",
        },
        {
            name: "黑白聚焦",
            pic: "3ab3eaae3d146911b8ee6957fd3f5f2a.jpg",
            link: "3ab3eaae3d146911b8ee6957fd3f5f2a.mp4",
            templateID: "6858cef24e178cd82c01ebcc",
        },
        {
            name: "醒目橙黄",
            pic: "891a38821cf8c5274b3f9ed78e0bc45b.jpg",
            link: "891a38821cf8c5274b3f9ed78e0bc45b.mp4",
            templateID: "6858cef24e178cd82c01ebcb",
        },
        {
            name: "粉白吸睛",
            pic: "7787c6143f348599f64e5814c2b90ef9.jpg",
            link: "7787c6143f348599f64e5814c2b90ef9.mp4",
            templateID: "6858cef24e178cd82c01ebca",
        },
        {
            name: "黄橙商务",
            pic: "e8e214a6da3c90b296ddd395f4114f59.jpg",
            link: "e8e214a6da3c90b296ddd395f4114f59.mp4",
            templateID: "6858cef24e178cd82c01ebc9",
        },
        {
            name: "活力粉白",
            pic: "da9a8c3585a5be0472804bc63c29c683.jpg",
            link: "da9a8c3585a5be0472804bc63c29c683.mp4",
            templateID: "6858cef24e178cd82c01ebc8",
        },
        {
            name: "棕黄简约",
            pic: "7163cb749fc06527037f7ab1fee36978.jpg",
            link: "7163cb749fc06527037f7ab1fee36978.mp4",
            templateID: "6858cef24e178cd82c01ebc7",
        },
        {
            name: "红蓝科普",
            pic: "ac11c543226137789cfa230642c22317.jpg",
            link: "ac11c543226137789cfa230642c22317.mp4",
            templateID: "6858cef24e178cd82c01ebc6",
        },
        {
            name: "简约黄白",
            pic: "ec05393d607cf6eed5e93280d77a6cb6.jpg",
            link: "ec05393d607cf6eed5e93280d77a6cb6.mp4",
            templateID: "6858cef24e178cd82c01ebc2",
        },
        {
            name: "常规黄白",
            pic: "7e59d0a78307baf60abeecb557a2946a.jpg",
            link: "7e59d0a78307baf60abeecb557a2946a.mp4",
            templateID: "6858cef24e178cd82c01ebc1",
        },
        {
            name: "简易橙黄",
            pic: "dd6b6bc9161254c6d9e70fe168d43150.jpg",
            link: "dd6b6bc9161254c6d9e70fe168d43150.mp4",
            templateID: "6858cef24e178cd82c01ebbf",
        },
    ],
    [MontageStylesChooseType.LOCAL]: [
        {
            name: "营销橙黄",
            pic: "3527ac138468b7af6fe8f95c90bde33f.jpg",
            link: "3527ac138468b7af6fe8f95c90bde33f.mp4",
            templateID: "68c2423f49c0ae002fc35e56",
        },
        {
            name: "黄白字幕",
            pic: "b2b80ed7d92a57ec7a174b836f90f682.jpg",
            link: "b2b80ed7d92a57ec7a174b836f90f682.mp4",
            templateID: "68c241c07d86fd0030fd34fe",
        },
        {
            name: "通用门店",
            pic: "276587d1e37836a8e2056336e1608b6a.jpg",
            link: "276587d1e37836a8e2056336e1608b6a.mp4",
            templateID: "6858cef34e178cd82c01ec11",
        },
        {
            name: "半透简黄",
            pic: "682fc05764c9bcd96a340eea7235df57.jpg",
            link: "682fc05764c9bcd96a340eea7235df57.mp4",
            templateID: "6858cef34e178cd82c01ec0e",
        },
        {
            name: "简约粉白",
            pic: "bb723c3eec85729b08841b7199a5bd94.jpg",
            link: "bb723c3eec85729b08841b7199a5bd94.mp4",
            templateID: "6858cef34e178cd82c01ec08",
        },
        {
            name: "网感营销",
            pic: "72a0746efae42c64b02332e8c41aae32.jpg",
            link: "72a0746efae42c64b02332e8c41aae32.mp4",
            templateID: "6858cef34e178cd82c01ec02",
        },
        {
            name: "门店营销",
            pic: "09abc6a0a7fc14798b59150e54bccb5c.jpg",
            link: "09abc6a0a7fc14798b59150e54bccb5c.mp4",
            templateID: "6858cef34e178cd82c01ec00",
        },
        {
            name: "通用黄",
            pic: "1f01c94fba35033409bc6bcfd9772ec3.jpg",
            link: "1f01c94fba35033409bc6bcfd9772ec3.mp4",
            templateID: "6858cef34e178cd82c01ebfd",
        },
        {
            name: "通用营销",
            pic: "7b3c9cf05ce401e01a73f6cac71402d0.jpg",
            link: "7b3c9cf05ce401e01a73f6cac71402d0.mp4",
            templateID: "6858cef34e178cd82c01ebf2",
        },
        {
            name: "简单黄白",
            pic: "266c11ab9302338cc42bcebcdd1e2aab.jpg",
            link: "266c11ab9302338cc42bcebcdd1e2aab.mp4",
            templateID: "6858cef34e178cd82c01ebda",
        },
    ],
    [MontageStylesChooseType.HIGH]: [
        {
            name: "简易黄",
            pic: "14d78f8339a2c54829420618d9624f5c.jpg",
            link: "14d78f8339a2c54829420618d9624f5c.mp4",
            templateID: "68c2417faf3b80003297aa3c",
        },
        {
            name: "简约双语",
            pic: "6fb4e247d15b1785a3419661745d9472.jpg",
            link: "6fb4e247d15b1785a3419661745d9472.mp4",
            templateID: "6879e391d13c7f0031567868",
        },
        {
            name: "黄白双语",
            pic: "8538f2c8fc78b45087d294f1265879f5.jpg",
            link: "8538f2c8fc78b45087d294f1265879f5.mp4",
            templateID: "6879b40dbf49c00033b0d9dc",
        },
        {
            name: "红白双语",
            pic: "484a5facb018944a15857eb100398915.jpg",
            link: "484a5facb018944a15857eb100398915.mp4",
            templateID: "6879b3e09afb52003109c4ce",
        },
        {
            name: "纯白双语",
            pic: "63ef3b270cba478061d4722563d57233.jpg",
            link: "63ef3b270cba478061d4722563d57233.mp4",
            templateID: "6879b376bf49c00033b0d9b7",
        },
        {
            name: "橙色大标题",
            pic: "8782f00d3c3599d2012a3808d32f51e9.jpg",
            link: "8782f00d3c3599d2012a3808d32f51e9.mp4",
            templateID: "6858cef34e178cd82c01ec0d",
        },
        {
            name: "专业蓝",
            pic: "85c2681c8c79fb5eda80e9f381de5feb.jpg",
            link: "85c2681c8c79fb5eda80e9f381de5feb.mp4",
            templateID: "6858cef34e178cd82c01ec09",
        },
        {
            name: "百搭黄",
            pic: "58631d5cd8a56b1a74baf4c42de18e45.jpg",
            link: "58631d5cd8a56b1a74baf4c42de18e45.mp4",
            templateID: "6858cef34e178cd82c01ec05",
        },
        {
            name: "蓝白调",
            pic: "8bf3f695c5fa7479ec19a12945568e7c.jpg",
            link: "8bf3f695c5fa7479ec19a12945568e7c.mp4",
            templateID: "6858cef34e178cd82c01ec01",
        },
        {
            name: "清新黄白",
            pic: "4a6f3efb23131665f3ee69861ca0b67f.jpg",
            link: "4a6f3efb23131665f3ee69861ca0b67f.mp4",
            templateID: "6858cef34e178cd82c01ebe7",
        },
        {
            name: "专业蓝白",
            pic: "b752f463b19fc38df28b5fc804991ed2.jpg",
            link: "b752f463b19fc38df28b5fc804991ed2.mp4",
            templateID: "6858cef34e178cd82c01ebe5",
        },
        {
            name: "红蓝标题",
            pic: "d56b070a45af5d923e12123c09897e47.jpg",
            link: "d56b070a45af5d923e12123c09897e47.mp4",
            templateID: "6858cef34e178cd82c01ebe4",
        },
        {
            name: "白橙风格",
            pic: "f39e9bb7898667914d82c998ee72da2a.jpg",
            link: "f39e9bb7898667914d82c998ee72da2a.mp4",
            templateID: "6858cef34e178cd82c01ebdd",
        },
        {
            name: "商务蓝白",
            pic: "030808c03d7af50907bfcbb3a0d4b1d6.jpg",
            link: "030808c03d7af50907bfcbb3a0d4b1d6.mp4",
            templateID: "6858cef34e178cd82c01ebd4",
        },
        {
            name: "高级橙白",
            pic: "6b071cdf2959b7b762139cd44c1d5017.jpg",
            link: "6b071cdf2959b7b762139cd44c1d5017.mp4",
            templateID: "6858cef34e178cd82c01ebd1",
        },
    ],
    [MontageStylesChooseType.VARIETY]: [
        {
            name: "重点橙红",
            pic: "eb4042907536e78b6793ee5a6006de51.jpg",
            link: "eb4042907536e78b6793ee5a6006de51.mp4",
            templateID: "68c2410da02ae200303a0aa7",
        },
        {
            name: "通用橙",
            pic: "86d82fa7921a95c343be2ad6b50fe218.jpg",
            link: "86d82fa7921a95c343be2ad6b50fe218.mp4",
            templateID: "6858cef34e178cd82c01ec03",
        },
        {
            name: "通用黄橙",
            pic: "81a72709e941302572041904358b49bf.jpg",
            link: "81a72709e941302572041904358b49bf.mp4",
            templateID: "6858cef34e178cd82c01ebf1",
        },
        {
            name: "实用橙黄",
            pic: "2d3e12c01da161b9969f99665a756b39.jpg",
            link: "2d3e12c01da161b9969f99665a756b39.mp4",
            templateID: "6858cef34e178cd82c01ebe0",
        },
        {
            name: "综艺渐变",
            pic: "8352b8f378daddb726dd2be6921e32d0.jpg",
            link: "8352b8f378daddb726dd2be6921e32d0.mp4",
            templateID: "6858cef34e178cd82c01ebdb",
        },
        {
            name: "活泼黄蓝",
            pic: "d4f521a5e6f0a5d1347d61c94e677e29.jpg",
            link: "d4f521a5e6f0a5d1347d61c94e677e29.mp4",
            templateID: "6858cef34e178cd82c01ebd2",
        },
        {
            name: "白红渐变",
            pic: "66d97bec1f6f4aa4c1b36c434744c163.jpg",
            link: "66d97bec1f6f4aa4c1b36c434744c163.mp4",
            templateID: "6858cef34e178cd82c01ebcf",
        },
        {
            name: "黑白聚焦",
            pic: "3ab3eaae3d146911b8ee6957fd3f5f2a.jpg",
            link: "3ab3eaae3d146911b8ee6957fd3f5f2a.mp4",
            templateID: "6858cef24e178cd82c01ebcc",
        },
        {
            name: "醒目橙黄",
            pic: "891a38821cf8c5274b3f9ed78e0bc45b.jpg",
            link: "891a38821cf8c5274b3f9ed78e0bc45b.mp4",
            templateID: "6858cef24e178cd82c01ebcb",
        },
        {
            name: "活力粉白",
            pic: "da9a8c3585a5be0472804bc63c29c683.jpg",
            link: "da9a8c3585a5be0472804bc63c29c683.mp4",
            templateID: "6858cef24e178cd82c01ebc8",
        },
    ],
    [MontageStylesChooseType.SIMPLE]: [
        {
            name: "经典黄白",
            pic: "f3a104977edc769dd1f3cff2772f79d9.jpg",
            link: "f3a104977edc769dd1f3cff2772f79d9.mp4",
            templateID: "68c240378dad360031c49acb",
        },
        {
            name: "蓝底商业",
            pic: "dd9d20d6df7cdd306eda4ff97b1e1430.jpg",
            link: "dd9d20d6df7cdd306eda4ff97b1e1430.mp4",
            templateID: "6858cef34e178cd82c01ec14",
        },
        {
            name: "黄白IP",
            pic: "ba756b255f8e61c22ebbc4195251085b.jpg",
            link: "ba756b255f8e61c22ebbc4195251085b.mp4",
            templateID: "6858cef34e178cd82c01ec10",
        },
        {
            name: "高级橙",
            pic: "3df4143976687f5bff0d08ea091a9731.jpg",
            link: "3df4143976687f5bff0d08ea091a9731.mp4",
            templateID: "6858cef34e178cd82c01ec0b",
        },
        {
            name: "经典黄",
            pic: "80ff13cfb4541ce2562a4d3bdc18c75e.jpg",
            link: "80ff13cfb4541ce2562a4d3bdc18c75e.mp4",
            templateID: "6858cef34e178cd82c01ec0a",
        },
        {
            name: "清新绿",
            pic: "c58dccc0737eda429c6697662d53c33f.jpg",
            link: "c58dccc0737eda429c6697662d53c33f.mp4",
            templateID: "6858cef34e178cd82c01ebfb",
        },
        {
            name: "诗意黄白",
            pic: "6ab819351e695b1a72056ad7b2a42d88.jpg",
            link: "6ab819351e695b1a72056ad7b2a42d88.mp4",
            templateID: "6858cef34e178cd82c01ebf0",
        },
        {
            name: "简易黄白",
            pic: "24669e1594a5a3bd8e5bc97b05f490a9.jpg",
            link: "24669e1594a5a3bd8e5bc97b05f490a9.mp4",
            templateID: "6858cef34e178cd82c01ebe3",
        },
        {
            name: "文艺黄白",
            pic: "6055f1ffb598b71d2e39837b32f3cf2b.jpg",
            link: "6055f1ffb598b71d2e39837b32f3cf2b.mp4",
            templateID: "6858cef34e178cd82c01ebdc",
        },
        {
            name: "淡雅黑白",
            pic: "eda30aa0aaa898f1ef0f151a1271c34f.jpg",
            link: "eda30aa0aaa898f1ef0f151a1271c34f.mp4",
            templateID: "6858cef34e178cd82c01ebd9",
        },
        {
            name: "棕黄风格",
            pic: "06ae41afda50cbf8d0f700b48fc0eaa9.jpg",
            link: "06ae41afda50cbf8d0f700b48fc0eaa9.mp4",
            templateID: "6858cef34e178cd82c01ebd8",
        },
        {
            name: "极简黄白",
            pic: "3c325f7fe6e147ac03ef83add8541f1a.jpg",
            link: "3c325f7fe6e147ac03ef83add8541f1a.mp4",
            templateID: "6858cef34e178cd82c01ebd6",
        },
        {
            name: "基础黄白",
            pic: "deff44e1dfef2d5b0afa92a7a0eba05a.jpg",
            link: "deff44e1dfef2d5b0afa92a7a0eba05a.mp4",
            templateID: "6858cef34e178cd82c01ebd3",
        },
    ],
    [MontageStylesChooseType.HOT]: [
        {
            name: "专业黄白",
            pic: "6e0e8dba7ed7de42972f719594566c89.jpg",
            link: "6e0e8dba7ed7de42972f719594566c89.mp4",
            templateID: "6858cef34e178cd82c01ec04",
        },
        {
            name: "通用黄白",
            pic: "e53d65ba2c738932149167bd633ad24e.jpg",
            link: "e53d65ba2c738932149167bd633ad24e.mp4",
            templateID: "6858cef34e178cd82c01ebee",
        },
        {
            name: "基础橙白",
            pic: "689133a2a659fe1e5eb099ae0910ee4b.jpg",
            link: "689133a2a659fe1e5eb099ae0910ee4b.mp4",
            templateID: "6858cef34e178cd82c01ebe1",
        },
        {
            name: "黄白字裂开",
            pic: "879130421b81c81be4e425ee15cfa059.jpg",
            link: "879130421b81c81be4e425ee15cfa059.mp4",
            templateID: "6858cef34e178cd82c01ebd5",
        },
        {
            name: "粉白渐变",
            pic: "42e15fb68931cb6f2b63015a2d50cb44.jpg",
            link: "42e15fb68931cb6f2b63015a2d50cb44.mp4",
            templateID: "6858cef34e178cd82c01ebd0",
        },
        {
            name: "黄橙商务",
            pic: "e8e214a6da3c90b296ddd395f4114f59.jpg",
            link: "e8e214a6da3c90b296ddd395f4114f59.mp4",
            templateID: "6858cef24e178cd82c01ebc9",
        },
        {
            name: "红蓝科普",
            pic: "ac11c543226137789cfa230642c22317.jpg",
            link: "ac11c543226137789cfa230642c22317.mp4",
            templateID: "6858cef24e178cd82c01ebc6",
        },
        {
            name: "简约黄白",
            pic: "ec05393d607cf6eed5e93280d77a6cb6.jpg",
            link: "ec05393d607cf6eed5e93280d77a6cb6.mp4",
            templateID: "6858cef24e178cd82c01ebc2",
        },
        {
            name: "常规黄白",
            pic: "7e59d0a78307baf60abeecb557a2946a.jpg",
            link: "7e59d0a78307baf60abeecb557a2946a.mp4",
            templateID: "6858cef24e178cd82c01ebc1",
        },
    ],
};

// 新闻体混剪模板数据
export const newsTemplates = {
    [MontageStylesChooseType.ALL]: [
        {
            name: "蓝红标题",
            pic: "11af18cf7f7e79018728fd09e7afcb0e.jpg",
            templateID: "69203fff18247100336f6031",
        },
        {
            name: "醒目标题",
            pic: "d86c0dc8b474640b11aee392713e257b.jpg",
            templateID: "68be53a18dad360031c30125",
        },
        {
            name: "七夕粉白字幕",
            pic: "c23330fdd8d0ef7df09c5d150de7d8c6.jpg",
            templateID: "68ae64298cb1b40031e2085a",
        },
        {
            name: "清新绿底",
            pic: "65657da2dbf5e7c0a335d5e0f9ff6765.jpg",
            templateID: "68872e4a48505d00319d3e26",
        },
        {
            name: "白橙标题-下",
            pic: "dead3e5ab6a2e0c5eeb40458e386d680.jpg",
            templateID: "6886ea414894d300312c0449",
        },
        {
            name: "白橙标题-上",
            pic: "bc38dc5175c00efbf3416262963bad5a.jpg",
            templateID: "6886ea2648505d00319d21b0",
        },
        {
            name: "蓝白标题",
            pic: "e0017ff8b997816a723eff96ced1330c.jpg",
            templateID: "6886e4d54894d300312c014c",
        },
        {
            name: "红黄标题-下",
            pic: "7a64a98f9162f23e929b3ca6cc9b053f.jpg",
            templateID: "6882132248505d00319bec20",
        },
        {
            name: "红黄标题-上",
            pic: "a073332e7539e0a80ae2c0dba827d312.jpg",
            templateID: "688212fe6de2ca00302da9e9",
        },
        {
            name: "常规黄黑-下",
            pic: "c73c9ad371670a85ba1bd1406c995c77.jpg",
            templateID: "6880c4ec6de2ca00302d4e50",
        },
        {
            name: "常规黄黑-上",
            pic: "bee048482eb6e44e5612d2e90bf1b508.jpg",
            templateID: "6880bd984761d400308c187b",
        },
        {
            name: "简约黄白",
            pic: "f7d195c050025045be2bc52860498c66.jpg",
            templateID: "6880b5974761d400308c15a5",
        },
        {
            name: "人设混剪2",
            pic: "6b2161bcc9b05d79aac2d2b0a069014f.jpg",
            templateID: "6880433948505d00319b5299",
        },
        {
            name: "经典黄白文案",
            pic: "47210ae18a0a8c9dd58a2600190ea830.jpg",
            templateID: "6865f0e80353060030b51900",
        },
        {
            name: "现场新闻",
            pic: "1dd6d9cf272f41d0b725f26a13dce5bf.jpg",
            templateID: "6863a624cbf589003866ed09",
        },
        {
            name: "蓝色三屏",
            pic: "91795c128f2e233e20c9ab8e4b78a17f.jpg",
            templateID: "6853e63e1e7b320034fa1d9f",
        },
        {
            name: "醒目红",
            pic: "68621307574bc8cfaec09916b675f77d.jpg",
            templateID: "6853cdd95440e50031367e4b",
        },
        {
            name: "人设混剪",
            pic: "9ac6c9593780346f5df5a6c066d42f4f.jpg",
            templateID: "6853cdbd0c508d0030268a8d",
        },
        {
            name: "蓝框热点",
            pic: "78af3517caafd367846d0572faa7800d.jpg",
            templateID: "684a7a1ad7c4ca003251c605",
        },
        {
            name: "黄色头条",
            pic: "51919ca7da1864abcf3db91ea4426956.jpg",
            templateID: "6847cfb9a6b684003173a2fb",
        },
        {
            name: "红黄黑框",
            pic: "df1cbc1ad26865cf6b082ab7d14864c6.jpg",
            templateID: "6846b4e41b0f5b0030239ae5",
        },
        {
            name: "红黄警示",
            pic: "b5ee6cff727dc2f7b27183987672bb07.jpg",
            templateID: "6846b24c1b0f5b0030239440",
        },
    ],
};

// 素材混剪模板数据
export const materialTemplates = {
    [MontageStylesChooseType.ALL]: [
        {
            name: "双层白红",
            pic: "d8761915bd77ccf902b69c24234bb4e2.webp",
            templateID: "68ca88e4be5c9d0032e6b9be",
        },
        {
            name: "分裂黄白",
            pic: "e51ac08fba4e82b58ec885b64294056c.webp",
            templateID: "68b800a17d86fd0030f969ff",
        },
        {
            name: "通用黄白",
            pic: "960ebd35220d440f66840ac4e209ee76.webp",
            templateID: "68b568321fc68f0030bc2133",
        },
        {
            name: "黄色双语",
            pic: "230adf9b0fafc9d085fb1693287f2305.webp",
            templateID: "68b535685fd04a002f002bfc",
        },
        {
            name: "七夕促销",
            pic: "d9497aca51182c2f06f9b748e2d5b84e.webp",
            templateID: "68aec28c6028800031de5ec6",
        },
        {
            name: "红白翻转大字",
            pic: "295e004898f36c8000d995b4d516a243.webp",
            templateID: "68809f2e5d8046003d902c47",
        },
        {
            name: "双语励志",
            pic: "a36d1a6a5a9ca581a53d76b2580ee89a.webp",
            templateID: "688056fa4761d400308be7e2",
        },
        {
            name: "半透黄白",
            pic: "c58a74ca08b6143d8b08e4d03462865f.jpg",
            templateID: "68639acd0353060030b4d2a6",
        },
        {
            name: "综艺三屏",
            pic: "65047e7979fb55f1934d87b61691d85f.jpg",
            templateID: "685529bb2b624a0030c84300",
        },
        {
            name: "科技三屏",
            pic: "c4926285cc3c8383595a994520670980.jpg",
            templateID: "685518926ee6cf0031425f0f",
        },
        {
            name: "新闻4",
            pic: "067878cedd8612ed02a2e6e64552a788.jpg",
            templateID: "685500add7c4ca00325301f3",
        },
        {
            name: "黑金风格",
            pic: "8247bacc4b93eb95663d3f5c9f9657f8.jpg",
            templateID: "6854d903a6b68400317528ce",
        },
        {
            name: "每日问候",
            pic: "d7aa8adf34384d302fcc5776a56d9fd0.jpg",
            templateID: "684feb8d64fc05003247b4a1",
        },
        {
            name: "新闻口播",
            pic: "7de0dcfa7dc20f6957eab1e6d72af815.jpg",
            templateID: "684be6e0f4c3530030d4792e",
        },
        {
            name: "情感口播",
            pic: "b49a7b75d8a94790def2339576e22122.webp",
            templateID: "684bc9c21b0f5b00302b69f9",
        },
        {
            name: "爆款混剪",
            pic: "5cd642d3d5b4a47b056aa43627d2154a.webp",
            templateID: "684261f814aa6c00308399e7",
        },
    ],
    [MontageStylesChooseType.LOCAL]: [
        {
            name: "通用黄白",
            pic: "960ebd35220d440f66840ac4e209ee76.webp",
            templateID: "68b568321fc68f0030bc2133",
        },
    ],
};
