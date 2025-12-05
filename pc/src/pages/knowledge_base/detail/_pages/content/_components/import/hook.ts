export interface IDataItem {
    name: string;
    path: string;
    size: number;
    data: {
        q: string;
        a: string;
    }[];
}

export interface IManualQAData extends IDataItem {
    files: any[];
    images: string[];
}

export enum ImportTypeEnum {
    //   MANUAL = 1,
    DOC = 1,
    CVS = 3,
    QASplit = 2,
    WEB_PAGE = 4,
}

//过滤相同文件
export const isSameFile = (file: File, fileList: File[]) => {
    return new Promise<string>((resolve, reject) => {
        const index = fileList.findIndex(({ name, lastModified, size }) => {
            return name === file.name && size === file.size && lastModified == file.lastModified;
        });
        if (index == -1) {
            resolve("");
        } else {
            reject("请勿选择相同文件！");
        }
    });
};
