<template>
    <div class="h-full flex gap-4 w-full">
        <div class="grow min-h-0 flex flex-col">
            <div>
                <ElTabs v-model="activeName" @tab-click="handleTabClick">
                    <ElTabPane v-for="item in typeLists" :key="item.id" :label="item.name" :name="item.id"></ElTabPane>
                </ElTabs>
                <ElAlert title="请注意：视频、小程序、链接、文件支持个微使用" type="warning" class="mt-2"></ElAlert>
                <div class="mt-2">
                    <div v-if="activeName === MaterialTypeEnum.TEXT" class="relative">
                        <ElInput
                            v-model="fileData.text"
                            ref="textInputRef"
                            type="textarea"
                            :autosize="{
                                minRows: 7,
                                maxRows: 15,
                            }"
                            placeholder="点击输入您要发送的文本内容" />

                        <div class="absolute bottom-2 w-full px-2 flex items-center justify-between">
                            <ElPopover
                                placement="bottom"
                                width="466"
                                trigger="click"
                                :show-arrow="false"
                                :popper-style="{
                                    padding: 0,
                                }">
                                <template #reference>
                                    <div class="rounded-lg hover:bg-token-sidebar-surface-secondary p-2 cursor-pointer">
                                        <Icon name="local-icon-phiz" :size="24" />
                                    </div>
                                </template>
                                <div>
                                    <emoji-container />
                                </div>
                            </ElPopover>
                            <ElButton type="primary" @click="insertRemark" v-if="false">插入备注</ElButton>
                        </div>
                    </div>
                    <div
                        v-else-if="[MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(activeName)"
                        class="w-full">
                        <upload
                            class="h-full w-full"
                            show-progress
                            :max-size="20"
                            :show-file-list="false"
                            :type="activeName == MaterialTypeEnum.IMAGE ? 'image' : 'video'"
                            @success="getUploadFile">
                            <div
                                class="h-[202px] border border-dashed border-[#dcdfe6] rounded-lg w-full flex flex-col items-center justify-center hover:border-primary">
                                <template
                                    v-if="
                                        activeName === MaterialTypeEnum.IMAGE
                                            ? !fileData.image.url
                                            : !fileData.video.url
                                    ">
                                    <img src="../_assets/images/add.png" class="w-8" />
                                    <div class="text-[#8A8A8A] mt-4">
                                        点击上传{{
                                            activeName === MaterialTypeEnum.IMAGE ? "图片" : "视频"
                                        }}，或点击此<span
                                            class="text-primary cursor-pointer hover:underline"
                                            @click.stop="openMaterialLibrary"
                                            >从素材库选择</span
                                        >
                                    </div>
                                </template>
                                <div v-else class="h-full w-full flex justify-center relative p-2">
                                    <img
                                        v-if="activeName === MaterialTypeEnum.IMAGE"
                                        :src="fileData.image.url"
                                        class="rounded-lg" />
                                    <video v-else :src="fileData.video.url" class="rounded-lg" />
                                    <div
                                        v-if="fileData.video.url"
                                        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 cursor-pointer z-30"
                                        @click.stop="handlePreviewVideo(fileData.video.url)">
                                        <play-btn />
                                    </div>
                                    <div class="absolute right-2 top-2">
                                        <ElButton
                                            @click.stop="
                                                activeName === MaterialTypeEnum.IMAGE
                                                    ? (fileData.image = {})
                                                    : (fileData.video = {})
                                            ">
                                            <Icon name="el-icon-Delete"></Icon>
                                        </ElButton>
                                    </div>
                                </div>
                            </div>
                        </upload>
                    </div>
                    <div
                        class="h-[202px] border border-[#dcdfe6] rounded-lg p-2"
                        v-else-if="
                            [MaterialTypeEnum.MINI_PROGRAM, MaterialTypeEnum.LINK, MaterialTypeEnum.FILE].includes(
                                activeName
                            )
                        ">
                        <div
                            v-if="fileData.miniProgram.name || fileData.link.name || fileData.file.name"
                            class="w-full h-full relative">
                            <div class="w-[50%] h-full mx-auto flex items-center justify-center">
                                <div
                                    class="h-full bg-primary-light-7 p-2 rounded-xl w-full"
                                    v-if="activeName == MaterialTypeEnum.MINI_PROGRAM">
                                    <MiniProgramCard
                                        :title="fileData.miniProgram.name"
                                        :pic="fileData.miniProgram.pic"
                                        :link="fileData.miniProgram.link" />
                                </div>
                                <div
                                    class="h-[70%] bg-primary-light-7 p-2 rounded-xl w-full"
                                    v-if="activeName == MaterialTypeEnum.LINK">
                                    <LinkCard
                                        :title="fileData.link.name"
                                        :desc="fileData.link.desc"
                                        :img="fileData.link.img" />
                                </div>
                                <div v-if="activeName == MaterialTypeEnum.FILE">
                                    <FileCard :name="fileData.file.name" :url="fileData.file.url" />
                                </div>
                            </div>
                            <div class="absolute right-2 top-2">
                                <ElButton
                                    @click.stop="
                                        fileData.miniProgram = {};
                                        fileData.link = {};
                                        fileData.file = {};
                                    ">
                                    <Icon name="el-icon-Delete"></Icon>
                                </ElButton>
                            </div>
                        </div>

                        <div v-else class="h-full flex flex-col items-center justify-center">
                            <img src="../_assets/images/add.png" class="w-8" />
                            <span
                                class="text-primary cursor-pointer hover:underline mt-4"
                                @click.stop="openMaterialLibrary"
                                >从素材库选择</span
                            >
                        </div>
                    </div>
                    <div class="flex justify-center mt-4">
                        <ElButton type="primary" @click="handleAddInfo"> 添加信息内容 </ElButton>
                    </div>
                </div>
                <div class="mt-8">
                    <div class="flex justify-center">
                        <img src="../_assets/images/arrow_down.png" class="w-[25px]" />
                    </div>
                </div>
            </div>
            <div class="grow min-h-0">
                <ElScrollbar>
                    <div class="px-4" v-draggable="draggableOptions">
                        <div class="material-list mt-4 flex flex-col gap-y-4">
                            <div
                                v-for="(item, index) in materialLists"
                                :key="index"
                                class="px-4 py-2 rounded-lg bg-[#2E2E2E] flex justify-between items-center">
                                <div class="text-white flex items-center gap-x-4 flex-1 w-0">
                                    <div class="flex-shrink-0 w-[100px]">【{{ getTypeName(item.type) }}】</div>
                                    <div
                                        class="flex-shrink-0"
                                        :class="{
                                            'flex-1 w-0': item.type == MaterialTypeEnum.TEXT,
                                        }">
                                        <div
                                            class="text-ellipsis overflow-hidden whitespace-nowrap"
                                            v-if="item.type == MaterialTypeEnum.TEXT">
                                            {{ item.content }}
                                        </div>
                                        <img
                                            v-if="item.type == MaterialTypeEnum.IMAGE"
                                            :src="item.content"
                                            class="w-6 h-6 rounded-lg object-cover" />
                                        <video
                                            v-if="item.type == MaterialTypeEnum.VIDEO"
                                            :src="item.content"
                                            class="w-6 h-6 rounded-lg object-cover" />
                                        <div v-if="item.type == MaterialTypeEnum.LINK">
                                            <img :src="item.content.img" class="w-6 h-6 rounded-lg object-cover" />
                                        </div>
                                        <div
                                            v-if="item.type == MaterialTypeEnum.MINI_PROGRAM"
                                            class="bg-white rounded-lg">
                                            <Icon
                                                name="local-icon-mini_program_fill"
                                                :size="20"
                                                color="var(--color-primary)"></Icon>
                                        </div>
                                        <div v-if="item.type == MaterialTypeEnum.FILE">
                                            <Icon name="local-icon-file_fill" color="#80B8F8" :size="20" />
                                        </div>
                                    </div>
                                    <div class="text-ellipsis overflow-hidden whitespace-nowrap">
                                        {{ typeof item.content == "object" ? item.content.name : "" }}
                                    </div>
                                </div>
                                <div class="flex items-center gap-x-2 flex-shrink-0">
                                    <div class="leading-[0] cursor-pointer" @click="delMaterial(index)">
                                        <Icon name="el-icon-Delete" color="#ffffff" />
                                    </div>
                                    <div class="move-icon cursor-move leading-[0]">
                                        <Icon name="el-icon-Rank" color="#ffffff" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
            </div>
        </div>
        <div class="w-[400px] flex-shrink-0" v-if="showPreview">
            <div class="phone-preview bg-primary-light-9 rounded-2xl px-10 py-[80px]">
                <ElScrollbar>
                    <div class="py-14 px-5 flex flex-col gap-y-2">
                        <div v-for="(item, index) in materialLists" :key="index" class="flex gap-x-2">
                            <div class="flex-shrink-0">
                                <img :src="userInfo.avatar" class="w-8 h-8 rounded-md" />
                            </div>
                            <div class="bg-white p-2 rounded-lg break-all" v-if="item.type == MaterialTypeEnum.TEXT">
                                {{ item.content }}
                            </div>
                            <div class="w-[120px] h-[120px]" v-if="item.type == MaterialTypeEnum.IMAGE">
                                <ElImage
                                    :src="item.content"
                                    :preview-src-list="[item.content]"
                                    fit="cover"
                                    class="w-full h-full" />
                            </div>
                            <div class="w-[120px] h-[120px] relative" v-if="item.type == MaterialTypeEnum.VIDEO">
                                <video :src="item.content" fit="cover" class="w-full h-full" />
                                <div
                                    class="absolute top-0 left-0 w-full h-full flex items-center justify-center bg-[#00000080] cursor-pointer"
                                    @click="handlePreviewVideo(item.content)">
                                    <Icon name="local-icon-play" :size="50" color="#ffffff"></Icon>
                                </div>
                            </div>
                            <div
                                class="bg-primary-light-8 h-[200px] w-full p-2 rounded-lg"
                                v-if="item.type == MaterialTypeEnum.MINI_PROGRAM">
                                <MiniProgramCard
                                    :title="item.content.name"
                                    :pic="item.content.pic"
                                    :link="item.content.link" />
                            </div>
                            <div
                                class="bg-primary-light-8 w-full p-2 rounded-lg"
                                v-if="item.type == MaterialTypeEnum.LINK">
                                <LinkCard
                                    :title="item.content.name"
                                    :desc="item.content.desc"
                                    :img="item.content.img" />
                            </div>
                            <div v-if="item.type == MaterialTypeEnum.FILE">
                                <FileCard :name="item.content.name" :url="item.content.url" />
                            </div>
                        </div>
                    </div>
                </ElScrollbar>
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
:deep(.el-textarea__inner) {
    padding-bottom: 50px;
}
:deep(.el-tabs__nav-wrap::after) {
    display: none;
}

.phone-preview {
    width: 100%;
    height: 728px;
    background-image: url("../_assets/images/phone.png");
    background-size: 100% 100%;
    background-repeat: no-repeat;
}
</style>
