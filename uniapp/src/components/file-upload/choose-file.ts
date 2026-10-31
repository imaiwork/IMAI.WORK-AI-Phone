import { ensurePrivacyAuthorized, isPrivacyError, privacyError, PRIVACY_TIP } from "@/utils/privacy";

const ERR_MSG_FAIL = "chooseFile:fail";
interface BaseOptions {
    type: "file" | "image" | "video" | "all";
}
type ChooseFileOptions = UniApp.ChooseFileOptions;
type ChooseMediaOption = UniApp.ChooseMediaOption;
type ChooseVideoOptions = UniApp.ChooseVideoOptions;
type ChooseMediaSuccessCallbackResult = UniApp.ChooseMediaSuccessCallbackResult;
type ChooseOptions = BaseOptions & (ChooseFileOptions | ChooseMediaOption | ChooseVideoOptions);

export interface ChooseResult {
    tempFilePaths: string[] | string;
    tempFiles: any[];
    failedCount: number;
    errMsg?: string;
    type?: "image" | "video";
}

export interface FileData {
    id?: number;
    name: string;
    uuid: number;
    extname: string;
    fileType: string;
    url: string;
    path: string;
    size: number;
    progress: number;
    status: "ready" | "success" | "error";
    errMsg: string;
}

/**
 * chooseMedia 只在部分小程序端透传到原生能力；H5/App 的 uni 运行时没有实现，
 * 调用直接 `TypeError: uni.chooseMedia is not a function`，所以这两端退回 chooseImage/chooseVideo。
 */
const canChooseMedia = (): boolean => typeof (uni as any).chooseMedia === "function";

/**
 * 统一失败出口：隐私类失败换成中文并带 privacy 标记（上层据此静默，不再弹英文 errMsg），
 * 其余保留原 errMsg 供上层判断（如 cancel / auth / deny）。
 */
function rejectChoose(reject: (reason?: any) => void, res: any, errPrefix: string) {
    if (isPrivacyError(res)) {
        uni.$u.toast(PRIVACY_TIP);
        reject(privacyError());
        return;
    }
    reject({
        errMsg: (res?.errMsg || "").replace(errPrefix, ERR_MSG_FAIL) || ERR_MSG_FAIL,
        errno: res?.errno,
    });
}

/**
 * 隐私协议闸门：未同意时原生接口必然失败且只回英文，这里先走官方授权弹窗。
 * 拒绝时用带 privacy 标记的错误 reject，避免上层再弹一次"上传失败"。
 */
async function withPrivacy(run: () => Promise<ChooseResult>): Promise<ChooseResult> {
    const authorized = await ensurePrivacyAuthorized();
    if (!authorized) {
        throw privacyError();
    }
    return run();
}

function chooseImage(opts: ChooseMediaOption) {
    const { count, sizeType = ["original", "compressed"], sourceType } = opts;
    // H5/App 兜底：uni.chooseImage 同样支持 count/sizeType/sourceType
    if (!canChooseMedia()) {
        return chooseImageByLegacy({ count, sizeType, sourceType });
    }
    return new Promise<ChooseResult>((resolve, reject) => {
        uni.chooseMedia({
            count,
            mediaType: ["image"],
            sizeType,
            sourceType,
            success(res: ChooseMediaSuccessCallbackResult) {
                const normalizedRes: ChooseResult = {
                    tempFilePaths: res.tempFiles.map((file) => file.tempFilePath),
                    tempFiles: res.tempFiles,
                    failedCount: 0,
                    errMsg: res.errMsg,
                };
                resolve(normalizeFileRes(normalizedRes, "image"));
            },
            fail(res: any) {
                // errno 112（后台未声明隐私类型）也必须 reject，否则 Promise 永远挂起，外层表现为"点了没反应"
                rejectChoose(reject, res, "chooseImage:fail");
            },
        });
    });
}

/** H5/App 兜底：返回的 tempFiles 只有 path，补成 tempFilePath 后才能走 normalizeFileRes */
function chooseImageByLegacy(opts: ChooseMediaOption) {
    return new Promise<ChooseResult>((resolve, reject) => {
        uni.chooseImage({
            count: opts.count,
            sizeType: opts.sizeType,
            sourceType: opts.sourceType,
            success(res: any) {
                // H5 的 tempFiles 是原生 File：name/size/path 都是原型或 defineProperty 上的 getter，
                // 用展开运算符拷不过来，必须逐个显式取值
                const tempFiles = (res.tempFiles || []).map((file: any) => {
                    const tempFilePath = file.tempFilePath || file.path || "";
                    return {
                        name: file.name || tempFilePath.substring(tempFilePath.lastIndexOf("/") + 1),
                        tempFilePath,
                        path: tempFilePath,
                        size: file.size || 0,
                    };
                });
                const normalizedRes: ChooseResult = {
                    tempFilePaths: res.tempFilePaths || tempFiles.map((file: any) => file.tempFilePath),
                    tempFiles,
                    failedCount: 0,
                    errMsg: res.errMsg,
                };
                resolve(normalizeFileRes(normalizedRes, "image"));
            },
            fail(res: any) {
                rejectChoose(reject, res, "chooseImage:fail");
            },
        });
    });
}

function chooseVideo(opts: ChooseMediaOption) {
    const { count, camera, maxDuration, sourceType, sizeType } = opts;
    // H5/App 兜底：uni.chooseVideo 只能单选，count > 1 会退化成一次选一个
    if (!canChooseMedia()) {
        return chooseVideoByLegacy({ camera, maxDuration, sourceType });
    }
    return new Promise<ChooseResult>((resolve, reject) => {
        uni.chooseMedia({
            count,
            mediaType: ["video"],
            camera,
            maxDuration,
            sourceType,
            sizeType,
            success(res) {
                resolve(normalizeFileRes(res as ChooseResult, "video"));
            },
            fail(res: any) {
                rejectChoose(reject, res, "chooseVideo:fail");
            },
        });
    });
}

/** H5/App 兜底：chooseVideo 返回扁平结构（tempFilePath/duration/size/width/height），包成 chooseMedia 形状 */
function chooseVideoByLegacy(opts: ChooseMediaOption) {
    return new Promise<ChooseResult>((resolve, reject) => {
        uni.chooseVideo({
            camera: opts.camera,
            maxDuration: opts.maxDuration,
            sourceType: opts.sourceType,
            compressed: true,
            success(res: any) {
                const normalizedRes: ChooseResult = {
                    tempFilePaths: [res.tempFilePath],
                    tempFiles: [
                        {
                            name: res.name || res.tempFile?.name || "",
                            tempFilePath: res.tempFilePath,
                            path: res.tempFilePath,
                            size: res.size || 0,
                            duration: res.duration || 0,
                            width: res.width || 0,
                            height: res.height || 0,
                        },
                    ],
                    failedCount: 0,
                    errMsg: res.errMsg,
                };
                resolve(normalizeFileRes(normalizedRes, "video"));
            },
            fail(res: any) {
                rejectChoose(reject, res, "chooseVideo:fail");
            },
        });
    });
}

function chooseAll(opts: ChooseFileOptions) {
    const { count, extension, type } = opts;
    return new Promise<ChooseResult>((resolve, reject) => {
        let chooseFile = uni.chooseFile;
        if (typeof wx !== "undefined" && typeof wx.chooseMessageFile === "function") {
            chooseFile = wx.chooseMessageFile;
        }
        if (typeof chooseFile !== "function") {
            return reject({
                errMsg: ERR_MSG_FAIL + " 请指定 type 类型，该平台仅支持选择 image 或 video。",
            });
        }
        chooseFile({
            type,
            count,
            extension,
            success(res) {
                resolve(normalizeFileRes(res as ChooseResult));
            },
            fail(res: any) {
                rejectChoose(reject, res, "chooseFile:fail");
            },
        });
    });
}

function normalizeFileRes(res: ChooseResult, fileType?: BaseOptions["type"]) {
    res.tempFiles.forEach((item) => {
        if (!item.name) {
            item.name = item.tempFilePath.substring(item.tempFilePath.lastIndexOf("/") + 1);
        }
        if (fileType) {
            item.fileType = fileType;
        }
        if (!item.tempFilePath) {
            item.tempFilePath = item.path;
        }
    });
    if (!res.tempFilePaths || res.tempFilePaths.length === 0) {
        res.tempFilePaths = res.tempFiles.map((file) => file.tempFilePath);
    }
    return res;
}

function chooseFile(
    opts: ChooseOptions = {
        type: "file",
    },
): Promise<ChooseResult> {
    if (opts.type === "image") {
        // 相册/相机属微信隐私接口，未同意隐私协议时原生必然失败且只回英文
        return withPrivacy(() => chooseImage(opts as ChooseMediaOption));
    } else if (opts.type === "video") {
        return withPrivacy(() => chooseVideo(opts as ChooseMediaOption));
    }
    // chooseMessageFile / chooseFile 不属隐私接口，不额外弹授权
    return chooseAll(opts as ChooseFileOptions);
}

/**
 * @description 获取文件扩展名和文件名
 * @param name
 * @returns
 */
const getFileExt = (name: string) => {
    const lastLen = name.lastIndexOf(".");
    const len = name.length;
    const fileName = name.substring(0, lastLen);
    const ext = name.substring(lastLen + 1, len);
    return {
        name: fileName,
        ext,
    };
};
/**
 * @description 路径中获取文件名称
 * @param name
 * @returns
 */
const getFileName = (path: string) => {
    const lastLen = path.lastIndexOf(".");
    const lastPath = path.lastIndexOf("/");
    // 不是文件
    if (lastLen === -1) return path;
    return path.substring(lastPath + 1);
};

const normalizeFileData = (files: any) => {
    const fileFullName = getFileExt(files.name);
    const extname = fileFullName.ext.toLowerCase();
    const filedata: FileData = {
        name: files.name,
        uuid: files.uuid,
        extname: extname || "",
        fileType: files.fileType,
        path: files.tempFilePath || files.path,
        url: files.tempFilePath || files.path,
        size: files.size,
        progress: 0,
        status: "ready",
        errMsg: "",
    };
    return filedata;
};

// 检验扩展名是否正确
const getFilesByExtname = (res: ChooseResult, extname: string[] = []) => {
    const filePaths: any[] = [];
    const files: any[] = [];
    if (!extname.length) {
        return {
            filePaths: res.tempFilePaths,
            files: res.tempFiles,
        };
    }
    res.tempFiles.forEach((v) => {
        const fileFullName = getFileExt(v.name);
        const ext = fileFullName.ext.toLowerCase();
        if (extname.indexOf(ext) !== -1) {
            files.push(v);
            filePaths.push(v.path);
        }
    });
    if (files.length !== res.tempFiles.length) {
        uni.showToast({
            title: `当前选择了${res.tempFiles.length}个文件 ，${res.tempFiles.length - files.length} 个文件格式不正确`,
            icon: "none",
            duration: 5000,
        });
    }
    return {
        filePaths,
        files,
    };
};

export { chooseFile, getFileExt, getFilesByExtname, normalizeFileData, getFileName };
