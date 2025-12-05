<template>
    <div class="h-full flex gap-4 w-full">
        <div class="grow min-h-0 flex flex-col">
            <div>
                <el-tabs v-model="activeName" @tab-click="handleTabClick">
                    <el-tab-pane
                        v-for="item in typeLists"
                        :key="item.id"
                        :label="item.name"
                        :name="item.id"></el-tab-pane>
                </el-tabs>
                <el-alert title="请注意：视频、小程序、链接、文件支持个微使用" type="warning" class="mt-2"></el-alert>
                <div class="mt-2">
                    <div v-if="activeName === MaterialTypeEnum.TEXT" class="relative">
                        <el-input
                            v-model="fileData.text"
                            ref="textInputRef"
                            type="textarea"
                            :autosize="{
                                minRows: 7,
                                maxRows: 15,
                            }"
                            placeholder="点击输入您要发送的文本内容" />
                    </div>
                    <div
                        v-else-if="[MaterialTypeEnum.IMAGE, MaterialTypeEnum.VIDEO].includes(activeName)"
                        class="w-full">
                        <upload
                            class="h-full w-full"
                            type="image"
                            show-progress
                            :max-size="20"
                            :show-file-list="false"
                            @success="getUploadFile">
                            <div
                                class="h-[202px] border border-dashed border-[#dcdfe6] rounded-lg w-full flex flex-col items-center justify-center hover:border-primary">
                                <template v-if="!fileData.image.url">
                                    <Icon name="el-icon-UploadFilled" :size="40"></Icon>
                                    <div class="text-[#8A8A8A] mt-4">点击上传图片</div>
                                </template>
                                <div v-else class="h-full w-full flex justify-center relative p-2">
                                    <img
                                        v-if="activeName === MaterialTypeEnum.IMAGE"
                                        :src="fileData.image.url"
                                        class="rounded-lg object-cover" />

                                    <div class="absolute right-2 top-2">
                                        <el-button @click.stop="fileData.image = {}">
                                            <Icon name="el-icon-Delete"></Icon>
                                        </el-button>
                                    </div>
                                </div>
                            </div>
                        </upload>
                    </div>
                    <div class="flex justify-center mt-4">
                        <el-button type="primary" @click="handleAddInfo"> 添加信息内容 </el-button>
                    </div>
                </div>
            </div>
            <div class="grow min-h-0">
                <el-scrollbar>
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
                </el-scrollbar>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import feedback from "@/utils/feedback";

enum MaterialTypeEnum {
    TEXT = 0,
    IMAGE = 1,
    VIDEO = 2,
    LINK = 3,
    MINI_PROGRAM = 4,
    FILE = 5,
}

// 定义类型
interface TextMaterialItem {
    type: MaterialTypeEnum.TEXT;
    content: string;
}

interface ImageMaterialItem {
    type: MaterialTypeEnum.IMAGE;
    content: string; // 图片URL
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

// 联合类型表示所有可能的素材项
type MaterialItem = TextMaterialItem | ImageMaterialItem;

interface FileDataType {
    text: string;
    image: Partial<MaterialContent>;
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
];

// 当前编辑的素材数据
const fileData = reactive<FileDataType>({
    text: "",
    image: {},
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
    uri: string;
    name: string;
}

const getUploadFile = (result: UploadResult): void => {
    const { uri, name } = result || {};

    if (!uri || !name) return;

    fileData.image = {
        url: uri,
        name,
    };
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
};

/**
 * 删除素材
 * @param index 要删除的素材索引
 */
const delMaterial = async (index: number): Promise<void> => {
    await feedback.confirm("确定删除该素材吗？");
    materialLists.value.splice(index, 1);
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
:deep(.el-upload) {
    width: 100%;
}
:deep(.el-textarea__inner) {
    padding-bottom: 50px;
}
:deep(.el-tabs__nav-wrap::after) {
    display: none;
}
</style>
