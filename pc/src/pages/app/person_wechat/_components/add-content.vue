<template>
    <div class="h-full flex gap-[24px] w-full bg-white">
        <div class="grow min-h-0 flex flex-col">
            <div class="editor-container">
                <ElTabs v-model="activeName" class="custom-editor-tabs" @tab-click="handleTabClick">
                    <ElTabPane v-for="item in typeLists" :key="item.id" :label="item.name" :name="item.id" />
                </ElTabs>

                <div
                    class="flex items-center gap-[8px] px-[12px] py-[8px] bg-[#eff6ff]/50 rounded-[8px] mb-[16px] border border-blue-50">
                    <span class="text-primary opacity-80 flex items-center gap-x-2">
                        <Icon name="el-icon-InfoFilled" />
                        <span class="text-xs text-[#0065fb]/80 font-medium">
                            提示：除文本外，其余格式目前仅支持个微环境使用
                        </span>
                    </span>
                </div>

                <div class="input-dynamic-area">
                    <div v-if="activeName === MaterialTypeEnum.TEXT" class="relative">
                        <ElInput
                            v-model="fileData.text"
                            ref="textInputRef"
                            type="textarea"
                            :autosize="{ minRows: 6, maxRows: 10 }"
                            placeholder="请输入消息文本内容..."
                            maxlength="500"
                            class="custom-textarea" />
                        <div class="absolute bottom-[10px] left-[12px] flex items-center gap-[8px]">
                            <ElPopover placement="top-start" width="400" trigger="click" popper-class="emoji-popper">
                                <template #reference>
                                    <div class="emoji-trigger-btn">
                                        <Icon name="local-icon-phiz" :size="20" />
                                    </div>
                                </template>
                                <emoji-container />
                            </ElPopover>
                        </div>
                    </div>

                    <div v-else-if="[MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(activeName)">
                        <upload
                            class="w-full"
                            show-progress
                            :max-size="20"
                            :show-file-list="false"
                            :type="activeName == MaterialTypeEnum.IMAGE ? 'image' : 'video'"
                            @success="getUploadFile">
                            <div class="upload-drop-box group">
                                <template
                                    v-if="
                                        activeName === MaterialTypeEnum.IMAGE
                                            ? !fileData.image.url
                                            : !fileData.video.url
                                    ">
                                    <div class="upload-icon-wrapper group-hover:scale-110 transition-transform">
                                        <Icon
                                            :name="
                                                activeName === MaterialTypeEnum.IMAGE
                                                    ? 'el-icon-Picture'
                                                    : 'el-icon-VideoCamera'
                                            "
                                            :size="32" />
                                    </div>
                                    <div class="text-[13px] text-tx-secondary mt-[12px]">
                                        点击上传或<span
                                            class="text-primary font-medium cursor-pointer hover:underline mx-[4px]"
                                            @click.stop="openMaterialLibrary"
                                            >从素材库选择</span
                                        >
                                    </div>
                                    <div class="text-[11px] text-tx-placeholder mt-[4px]">
                                        支持 JPG/PNG/MP4，大小不超过 20MB
                                    </div>
                                </template>
                                <div v-else class="preview-uploaded relative group/item">
                                    <img
                                        v-if="activeName === MaterialTypeEnum.IMAGE"
                                        :src="fileData.image.url"
                                        class="preview-content" />
                                    <video v-else :src="fileData.video.url" class="preview-content" />
                                    <div
                                        v-if="fileData.video.url"
                                        class="play-overlay"
                                        @click.stop="handlePreviewVideo(fileData.video.url)">
                                        <Icon name="el-icon-VideoPlay" :size="48" />
                                    </div>
                                    <div class="preview-actions">
                                        <div
                                            class="action-circle-btn"
                                            @click.stop="
                                                activeName === MaterialTypeEnum.IMAGE
                                                    ? (fileData.image = {})
                                                    : (fileData.video = {})
                                            ">
                                            <Icon name="el-icon-Delete" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </upload>
                    </div>

                    <div v-else class="card-upload-area">
                        <div
                            v-if="fileData.miniProgram.name || fileData.link.name || fileData.file.name"
                            class="relative p-[16px] h-full flex items-center justify-center">
                            <div class="w-[280px]">
                                <MiniProgramCard
                                    v-if="activeName == MaterialTypeEnum.MINI_PROGRAM"
                                    :title="fileData.miniProgram.name"
                                    :pic="fileData.miniProgram.pic"
                                    :link="fileData.miniProgram.link" />
                                <LinkCard
                                    v-if="activeName == MaterialTypeEnum.LINK"
                                    :title="fileData.link.name"
                                    :desc="fileData.link.desc"
                                    :img="fileData.link.img" />
                                <FileCard
                                    v-if="activeName == MaterialTypeEnum.FILE"
                                    :name="fileData.file.name"
                                    :url="fileData.file.url" />
                            </div>
                            <div
                                class="absolute right-[12px] top-[12px] action-circle-btn shadow-md"
                                @click="resetFileData">
                                <Icon name="el-icon-Delete" />
                            </div>
                        </div>
                        <div v-else class="flex flex-col items-center justify-center h-full">
                            <div class="upload-icon-wrapper">
                                <Icon name="el-icon-FolderOpened" :size="32" />
                            </div>
                            <span
                                class="text-primary font-medium cursor-pointer hover:underline mt-[12px]"
                                @click.stop="openMaterialLibrary">
                                点击进入素材库选择
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-center mt-[24px]">
                        <ElButton type="primary" @click="handleAddInfo">
                            <Icon name="el-icon-Plus" />
                            <span class="ml-1"> 确认添加至队列 </span>
                        </ElButton>
                    </div>
                </div>
            </div>

            <div class="grow min-h-0 mt-[20px] flex flex-col">
                <div class="flex items-center gap-[8px] mb-[12px]">
                    <span class="text-[14px] font-[900] text-tx-primary">待发送队列</span>
                    <span class="text-xs text-tx-secondary opacity-60">(共 {{ materialLists.length }}/6 条)</span>
                </div>
                <ElScrollbar>
                    <div v-draggable="draggableOptions">
                        <div class="material-list flex flex-col gap-[12px] pr-[12px]">
                            <div v-for="(item, index) in materialLists" :key="index" class="material-queue-item group">
                                <div class="flex items-center gap-[12px] flex-1 min-w-0">
                                    <div class="type-tag" :class="`type-${item.type}`">
                                        {{ getTypeName(item.type).replace("消息", "") }}
                                    </div>
                                    <div class="flex-1 text-[13px] text-tx-primary font-medium">
                                        <div class="line-clamp-1 break-all" v-if="item.type == MaterialTypeEnum.TEXT">
                                            {{ item.content }}
                                        </div>
                                        <template v-else-if="item.type == MaterialTypeEnum.IMAGE">[图片素材]</template>
                                        <template v-else-if="item.type == MaterialTypeEnum.VIDEO">[视频素材]</template>
                                        <template v-else>{{ (item.content as any).name }}</template>
                                    </div>
                                </div>
                                <div class="flex items-center gap-[12px]">
                                    <div class="action-icon hover:text-error" @click="delMaterial(index)">
                                        <Icon name="el-icon-Delete" />
                                    </div>
                                    <div class="action-icon move-icon cursor-move">
                                        <Icon name="el-icon-Rank" />
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="materialLists.length === 0"
                                class="border-2 border-dashed border-gray-100 rounded-xl h-[100px] flex items-center justify-center text-tx-placeholder text-[13px]">
                                暂无发送任务，请在上方添加
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>

        <div class="w-[360px] flex-shrink-0 flex flex-col" v-if="showPreview">
            <div class="preview-header mb-[12px] flex items-center gap-[8px]">
                <Icon name="el-icon-View" color="var(--color-primary)" />
                <span class="text-[14px] font-[900]">发送效果预览</span>
            </div>
            <div class="phone-mockup">
                <div class="phone-screen">
                    <div class="wechat-header">
                        <div class="status-bar">
                            <span>9:41</span>
                            <div class="flex gap-[4px]">
                                <Icon name="el-icon-Connection" /><Icon name="el-icon-BatteryFilled" />
                            </div>
                        </div>
                        <div class="title-bar">
                            <Icon name="el-icon-ArrowLeft" />
                            <span class="truncate">客户名称</span>
                            <Icon name="el-icon-More" />
                        </div>
                    </div>

                    <ElScrollbar>
                        <div class="chat-content flex flex-col gap-[16px]">
                            <div v-for="(item, index) in materialLists" :key="index" class="chat-bubble-row">
                                <img :src="userInfo.avatar" class="avatar" />
                                <div class="bubble-container">
                                    <div v-if="item.type == MaterialTypeEnum.TEXT" class="text-bubble">
                                        {{ item.content }}
                                    </div>
                                    <div
                                        v-else-if="item.type == MaterialTypeEnum.IMAGE"
                                        class="image-bubble shadow-light">
                                        <ElImage
                                            :src="item.content as string"
                                            fit="cover"
                                            :preview-src-list="[item.content as string]" />
                                    </div>
                                    <div
                                        v-else-if="item.type == MaterialTypeEnum.VIDEO"
                                        class="video-bubble shadow-light">
                                        <video :src="item.content as string" />
                                        <div class="play-icon" @click="handlePreviewVideo(item.content as string)">
                                            <Icon name="el-icon-VideoPlay" :size="30" />
                                        </div>
                                    </div>
                                    <div v-else class="card-bubble shadow-light">
                                        <div v-if="item.type == MaterialTypeEnum.MINI_PROGRAM" class="mini-card-mini">
                                            <div class="flex items-center gap-[4px] mb-[4px] opacity-70">
                                                <Icon name="local-icon-mini_program_fill" :size="12" color="#0065fb" />
                                                <span class="text-[10px]">小程序</span>
                                            </div>
                                            <div class="text-[13px] font-medium line-clamp-2 mb-[8px]">
                                                {{ (item.content as any).name }}
                                            </div>
                                            <img
                                                :src="(item.content as any).pic"
                                                class="w-full h-[120px] object-cover rounded-[4px]" />
                                        </div>
                                        <div
                                            v-if="item.type == MaterialTypeEnum.LINK"
                                            class="link-card-mini flex gap-[8px]">
                                            <div class="flex-1 min-w-0">
                                                <div class="text-[13px] font-medium line-clamp-2">
                                                    {{ (item.content as any).name }}
                                                </div>
                                                <div class="text-[11px] text-tx-secondary mt-[4px] line-clamp-2">
                                                    {{ (item.content as any).desc }}
                                                </div>
                                            </div>
                                            <img
                                                :src="(item.content as any).img"
                                                class="w-[44px] h-[44px] rounded-[4px] object-cover" />
                                        </div>
                                        <div
                                            v-if="item.type == MaterialTypeEnum.FILE"
                                            class="file-card-mini flex items-center gap-[10px]">
                                            <Icon name="local-icon-file_fill" color="#80B8F8" :size="32" />
                                            <div class="text-[13px] font-medium truncate flex-1">
                                                {{ (item.content as any).name }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>
                </div>
            </div>
        </div>
    </div>
    <preview-video v-if="showVideo" ref="videoPreviewPlayerRef" @close="showVideo = false"></preview-video>
    <material-picker
        v-if="showMaterialPicker"
        ref="materialPickerRef"
        :limit="1"
        :type="activeName"
        @close="showMaterialPicker = false"
        @select="handleSelectMaterial" />
</template>
<script setup lang="ts">
import { setRangeText } from "@/utils/dom";
import { dayjs, type InputInstance } from "element-plus";
import { useUserStore } from "@/stores/user";
import { MaterialTypeEnum, HandleEventEnum } from "../_enums";
import EmojiContainer from "./emoji.vue";
import useHandle from "../_hooks/useHandle";
import MiniProgramCard from "./mini-program-card.vue";
import MaterialPicker from "./material-picker.vue";
import LinkCard from "./link-card.vue";
import FileCard from "./file-card.vue";

// 定义类型
interface TextMaterialItem {
    type: MaterialTypeEnum.TEXT;
    content: string;
}

interface ImageMaterialItem {
    type: MaterialTypeEnum.IMAGE;
    content: string; // 图片URL
}

interface VideoMaterialItem {
    type: MaterialTypeEnum.VIDEO;
    content: string; // 视频URL
}

interface MaterialContent {
    name: string;
    url?: string;
    uri?: string;
    desc?: string;
    img?: string;
    pic?: string;
    link?: string;
    appid?: string;
}

interface MiniProgramMaterialItem {
    type: MaterialTypeEnum.MINI_PROGRAM;
    content: {
        name: string;
        pic: string;
        link: string;
        appid?: string;
    };
}

interface LinkMaterialItem {
    type: MaterialTypeEnum.LINK;
    content: {
        name: string;
        desc: string;
        img: string;
        link: string;
    };
}

interface FileMaterialItem {
    type: MaterialTypeEnum.FILE;
    content: {
        name: string;
        url: string;
    };
}

// 联合类型表示所有可能的素材项
type MaterialItem =
    | TextMaterialItem
    | ImageMaterialItem
    | VideoMaterialItem
    | MiniProgramMaterialItem
    | LinkMaterialItem
    | FileMaterialItem;

interface FileDataType {
    text: string;
    image: Partial<MaterialContent>;
    video: Partial<MaterialContent>;
    miniProgram: Partial<MaterialContent>;
    link: Partial<MaterialContent>;
    file: Partial<MaterialContent>;
}

interface TypeListItem {
    id: MaterialTypeEnum;
    key: string;
    name: string;
    error_tips: string;
}

const props = withDefaults(
    defineProps<{
        type?: 1 | 2;
        modelValue: MaterialItem[];
        showPreview?: boolean;
    }>(),
    {
        showPreview: true,
        type: 1,
    }
);

const emit = defineEmits<{
    (e: "update:modelValue", value: MaterialItem[]): void;
}>();

const userStore = useUserStore();
const { userInfo } = toRefs(userStore);

const nuxtApp = useNuxtApp();

// 使用v-model双向绑定处理素材列表
const materialLists = computed<MaterialItem[]>({
    get() {
        return props.modelValue;
    },
    set(value) {
        emit("update:modelValue", value);
    },
});

// 当前选中的素材类型
const activeName = ref<MaterialTypeEnum>(MaterialTypeEnum.TEXT);

// 素材类型列表
const typeLists: TypeListItem[] = [
    {
        id: MaterialTypeEnum.TEXT,
        key: "text",
        name: "文本",
        error_tips: "请输入文本内容",
    },
    {
        id: MaterialTypeEnum.IMAGE,
        key: "image",
        name: "图片",
        error_tips: "请上传图片",
    },
    {
        id: MaterialTypeEnum.VIDEO,
        key: "video",
        name: "视频",
        error_tips: "请上传视频",
    },
    {
        id: MaterialTypeEnum.MINI_PROGRAM,
        key: "miniProgram",
        name: "小程序",
        error_tips: "请选择小程序",
    },
    {
        id: MaterialTypeEnum.LINK,
        key: "link",
        name: "链接",
        error_tips: "请选择链接",
    },
    {
        id: MaterialTypeEnum.FILE,
        key: "file",
        name: "文件",
        error_tips: "请选择文件",
    },
];
// const typeLists = computed(() => {
//     return props.type == 2
//         ? [
//               {
//                   id: MaterialTypeEnum.TEXT,
//                   key: "text",
//                   name: "文本",
//                   error_tips: "请输入文本内容",
//               },
//               {
//                   id: MaterialTypeEnum.IMAGE,
//                   key: "image",
//                   name: "图片",
//                   error_tips: "请上传图片",
//               },
//           ]
//         : [
//               {
//                   id: MaterialTypeEnum.TEXT,
//                   key: "text",
//                   name: "文本",
//                   error_tips: "请输入文本内容",
//               },
//               {
//                   id: MaterialTypeEnum.IMAGE,
//                   key: "image",
//                   name: "图片",
//                   error_tips: "请上传图片",
//               },
//               {
//                   id: MaterialTypeEnum.VIDEO,
//                   key: "video",
//                   name: "视频",
//                   error_tips: "请上传视频",
//               },
//               {
//                   id: MaterialTypeEnum.MINI_PROGRAM,
//                   key: "miniProgram",
//                   name: "小程序",
//                   error_tips: "请选择小程序",
//               },
//               {
//                   id: MaterialTypeEnum.LINK,
//                   key: "link",
//                   name: "链接",
//                   error_tips: "请选择链接",
//               },
//               {
//                   id: MaterialTypeEnum.FILE,
//                   key: "file",
//                   name: "文件",
//                   error_tips: "请选择文件",
//               },
//           ];
// });

// 当前编辑的素材数据
const fileData = reactive<FileDataType>({
    text: "",
    image: {},
    video: {},
    miniProgram: {},
    link: {},
    file: {},
});

// 拖拽配置选项
const draggableOptions = [
    {
        selector: ".material-list",
        options: {
            animation: 150,
            handle: ".move-icon",
            onEnd: ({ newIndex, oldIndex }: { newIndex: number; oldIndex: number }) => {
                // 处理拖拽结束后的排序逻辑
                const arr = [...materialLists.value];
                const currRow = arr.splice(oldIndex, 1)[0];
                arr.splice(newIndex, 0, currRow);

                // 使用临时空数组和nextTick触发视图更新
                materialLists.value = [];
                nextTick(() => {
                    materialLists.value = arr;
                });
            },
        },
    },
];

// 处理外部事件
const { onHandleEvent } = useHandle();

// 监听emoji选择等事件
interface ActionEventData {
    type: HandleEventEnum;
    emoji?: string;
    [key: string]: any;
}

onHandleEvent("action", (data: ActionEventData) => {
    const { type } = data;
    switch (type) {
        case HandleEventEnum.ChooseEmoji:
            if (textInputRef.value?.textarea && data.emoji) {
                fileData.text = setRangeText(textInputRef.value.textarea, data.emoji);
            }
            break;
    }
});

/**
 * 处理标签页切换事件
 */
const handleTabClick = () => {
    resetFileData();
};

/**
 * 根据素材类型获取类型名称
 * @param type 素材类型枚举值
 * @returns 格式化后的类型名称
 */
const getTypeName = (type: MaterialTypeEnum): string => {
    return typeLists.find((item) => item.id == type)?.name + "消息" || "未知类型";
};

/**
 * 处理文件上传成功回调
 * @param result 上传结果
 */
interface UploadResult {
    data?: {
        uri: string;
        name: string;
    };
}

const getUploadFile = (result: UploadResult): void => {
    const { uri, name } = result.data || {};

    if (!uri || !name) return;

    if (activeName.value === MaterialTypeEnum.IMAGE) {
        fileData.image = {
            url: uri,
            name,
        };
    } else {
        fileData.video = {
            url: uri,
            name,
        };
    }
};

// 视频预览相关状态
const showVideo = ref<boolean>(false);
const videoPreviewPlayerRef = shallowRef();
const previewVideoUrl = ref<string>("");

/**
 * 处理视频预览
 * @param uri 视频地址
 */
const handlePreviewVideo = async (uri: string): Promise<void> => {
    if (!uri) return;

    previewVideoUrl.value = uri;
    showVideo.value = true;

    await nextTick();

    if (videoPreviewPlayerRef.value) {
        videoPreviewPlayerRef.value.open();
        videoPreviewPlayerRef.value.setUrl(uri);
    }
};

// 素材选择器相关状态
const showMaterialPicker = ref<boolean>(false);
const materialPickerRef = shallowRef<InstanceType<typeof MaterialPicker>>();

/**
 * 打开素材库选择器
 */
const openMaterialLibrary = async (): Promise<void> => {
    showMaterialPicker.value = true;
    await nextTick();
    materialPickerRef.value?.open();
};

const handleSelectMaterial = (value: any) => {
    const { ext_info, file_name, file_url } = value;

    // 关闭素材选择器
    showMaterialPicker.value = false;

    // 根据当前选中的素材类型处理数据
    switch (activeName.value) {
        case MaterialTypeEnum.IMAGE:
            fileData.image = {
                url: file_url,
                name: file_name,
            };
            break;

        case MaterialTypeEnum.VIDEO:
            fileData.video = {
                url: file_url,
                name: file_name,
            };
            break;

        case MaterialTypeEnum.LINK:
            fileData.link = {
                name: file_name,
                img: file_url,
                desc: ext_info.link_desc || "",
                link: ext_info.link || "",
            };
            break;

        case MaterialTypeEnum.MINI_PROGRAM:
            fileData.miniProgram = {
                name: file_name,
                pic: file_url,
                link: ext_info.mini_program_path || "",
                appid: ext_info.mini_program_appid,
            };
            break;

        case MaterialTypeEnum.FILE:
            fileData.file = {
                name: file_name,
                url: file_url,
            };
            break;
    }
};

// 文本输入框引用
const textInputRef = shallowRef<InputInstance>();

/**
 * 插入备注标记
 */
const insertRemark = (): void => {
    if (textInputRef.value?.textarea) {
        fileData.text = setRangeText(textInputRef.value.textarea, `\${remark}`);
    }
};

/**
 * 添加信息内容到列表
 */
const handleAddInfo = (): void => {
    // 检查列表长度限制
    const MAX_ITEMS = 6;
    if (materialLists.value.length >= MAX_ITEMS) {
        feedback.msgError(`最多只能添加${MAX_ITEMS}条信息`);
        return;
    }

    // 获取当前选中的素材类型信息
    const currData = typeLists.find((item) => item.id === activeName.value);
    if (!currData) return;

    // 检查必填内容
    const currentValue = fileData[currData.key as keyof FileDataType];
    const isEmpty = typeof currentValue === "string" ? !currentValue : !Object.keys(currentValue).length;

    if (isEmpty) {
        feedback.msgError(currData.error_tips);
        return;
    }

    // 根据当前选中的素材类型添加对应的内容
    let newItem: MaterialItem;

    switch (activeName.value) {
        case MaterialTypeEnum.TEXT:
            newItem = {
                type: MaterialTypeEnum.TEXT,
                content: fileData.text,
            };
            break;

        case MaterialTypeEnum.IMAGE:
            newItem = {
                type: MaterialTypeEnum.IMAGE,
                content: fileData.image.url || "",
            };
            break;

        case MaterialTypeEnum.VIDEO:
            newItem = {
                type: MaterialTypeEnum.VIDEO,
                content: fileData.video.url || "",
            };
            break;

        case MaterialTypeEnum.MINI_PROGRAM:
            newItem = {
                type: MaterialTypeEnum.MINI_PROGRAM,
                content: {
                    name: fileData.miniProgram.name || "",
                    pic: fileData.miniProgram.pic || "",
                    link: fileData.miniProgram.link || "",
                    appid: fileData.miniProgram.appid,
                },
            };
            break;

        case MaterialTypeEnum.LINK:
            newItem = {
                type: MaterialTypeEnum.LINK,
                content: {
                    name: fileData.link.name || "",
                    desc: fileData.link.desc || "",
                    img: fileData.link.img || "",
                    link: fileData.link.link || "",
                },
            };
            break;

        case MaterialTypeEnum.FILE:
            newItem = {
                type: MaterialTypeEnum.FILE,
                content: {
                    name: fileData.file.name || "",
                    url: fileData.file.url || "",
                },
            };
            break;

        default:
            return; // 未知类型，不处理
    }

    // 添加到列表
    materialLists.value = [...materialLists.value, newItem];

    // 重置表单数据
    resetFileData();
};

/**
 * 重置表单数据
 */
const resetFileData = (): void => {
    // 重置文本为空字符串
    fileData.text = "";

    // 重置各类型对象为初始状态
    fileData.image = { url: "", name: "" };
    fileData.video = { url: "", name: "" };
    fileData.miniProgram = { name: "", pic: "", link: "", appid: undefined };
    fileData.link = { name: "", desc: "", img: "" };
    fileData.file = { name: "", url: "" };
};

/**
 * 删除素材
 * @param index 要删除的素材索引
 */
const delMaterial = async (index: number): Promise<void> => {
    nuxtApp.$confirm({
        message: "确定删除该素材吗？",
        onConfirm: () => {
            materialLists.value.splice(index, 1);
        },
    });
};

/**
 * 打开组件方法
 * 供父组件调用
 */
const open = (): void => {
    // 初始化组件状态
    resetFileData();
    activeName.value = MaterialTypeEnum.TEXT;
};

// 导出组件方法
defineExpose({
    open,
});
</script>
<style scoped lang="scss">
.editor-container {
    @apply p-[20px] bg-gray-50 rounded-xl border border-br-extra-light;
}

:deep(.custom-editor-tabs) {
    .el-tabs__nav-wrap::after {
        display: none;
    }
    .el-tabs__item {
        @apply text-[14px] font-medium px-[16px];
    }
}

:deep(.material-textarea) {
    .el-textarea__inner {
        @apply bg-white border-br rounded-xl p-[12px] pb-[40px] shadow-[none] transition-all;
        &:focus {
            @apply border-primary shadow-[0_0_0_2px_rgba(0,101,251,0.05)];
        }
    }
}

.emoji-trigger-btn {
    @apply w-[32px] h-[32px] flex items-center justify-center rounded-lg text-tx-secondary hover:bg-gray-100 transition-all cursor-pointer;
}

.upload-drop-box {
    @apply w-full h-[220px] border-2 border-dashed border-br bg-white rounded-xl flex flex-col items-center justify-center transition-all cursor-pointer;
    &:hover {
        @apply border-primary bg-[#eff6ff]/20;
    }
}

.upload-icon-wrapper {
    @apply w-[64px] h-[64px] rounded-full bg-gray-50 flex items-center justify-center text-tx-placeholder;
}

.preview-uploaded {
    @apply h-full w-full flex items-center justify-center p-[12px];
    .preview-content {
        @apply max-h-full max-w-full rounded-lg shadow-light border border-br;
    }
}

.play-overlay {
    @apply absolute inset-0 flex items-center justify-center bg-[#000000]/30 text-white rounded-lg opacity-80 hover:opacity-100 transition-all;
}

.add-material-btn {
    @apply h-[42px] rounded-lg px-[24px] font-medium shadow-light;
}

.material-queue-item {
    @apply px-[16px] py-[12px] bg-white border border-br rounded-xl flex items-center justify-between transition-all;
    &:hover {
        @apply border-primary-light-7 shadow-light bg-[#f5f5f5]/10;
    }

    .type-tag {
        @apply text-[10px] px-[8px] py-[2px] rounded-full font-black text-white;
        &.type-0 {
            @apply bg-primary;
        }
        &.type-1 {
            @apply bg-blue-500;
        }
        &.type-2 {
            @apply bg-orange-500;
        }
        &.type-3 {
            @apply bg-purple-500;
        }
        &.type-4 {
            @apply bg-green-500;
        }
        &.type-5 {
            @apply bg-sky-500;
        }
        &.type-6 {
            @apply bg-gray-500;
        }
    }

    .action-icon {
        @apply text-tx-placeholder cursor-pointer hover:text-primary transition-colors;
    }
}

.phone-mockup {
    @apply w-[300px] h-[620px] bg-gray-950 rounded-[40px] border-[8px] border-gray-900 relative shadow-light self-center;
    .phone-screen {
        @apply h-full w-full bg-[#EDEDED] rounded-[32px] overflow-hidden flex flex-col;
    }
}

.wechat-header {
    @apply bg-[#EDEDED] border-b border-gray-200 shrink-0;
    .status-bar {
        @apply flex justify-between px-[20px] py-[6px] text-[10px] font-medium text-gray-950;
    }
    .title-bar {
        @apply flex items-center justify-between px-[12px] py-[10px] text-gray-950;
        font-weight: bold;
    }
}

.chat-content {
    @apply p-[12px] pb-[40px];
}

.chat-bubble-row {
    @apply flex gap-[8px];
    .avatar {
        @apply w-[36px] h-[36px] rounded-[6px] bg-white border border-gray-200;
    }
    .bubble-container {
        @apply flex-1 max-w-[80%];
    }
}

.text-bubble {
    @apply bg-white p-[10px] rounded-[4px] rounded-tl-none text-[13px] text-gray-950 relative shadow-light  break-all;
    &::after {
        content: "";
        @apply absolute -left-[6px] top-0 w-0 h-0 border-[6px] border-[transparent] border-t-white;
    }
}

.card-bubble {
    @apply bg-white p-[10px] rounded-[4px] relative shadow-light;
}

.action-circle-btn {
    @apply w-[28px] h-[28px] rounded-full bg-white text-tx-secondary flex items-center justify-center cursor-pointer hover:bg-error hover:text-white transition-all;
}
</style>
