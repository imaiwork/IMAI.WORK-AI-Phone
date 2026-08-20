<template>
    <popup
        ref="popupRef"
        async
        width="500px"
        :title="mode === 'add' ? '添加教程' : '编辑教程'"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div>
            <el-form :model="formData" :rules="rules" ref="formRef" label-width="100px">
                <el-form-item label="主标题" prop="title">
                    <el-input v-model="formData.title" placeholder="请输入主标题" maxlength="20" show-word-limit />
                </el-form-item>

                <el-form-item label="主内容类型" prop="main_type">
                    <el-radio-group v-model="formData.main_type" @change="handleMainTypeChange">
                        <el-radio-button :value="1">视频</el-radio-button>
                        <el-radio-button :value="2">图片</el-radio-button>
                    </el-radio-group>
                </el-form-item>

                <el-form-item v-if="formData.main_type == 1" label="主视频" prop="main_url">
                    <material-picker type="video" v-model="formData.main_url" :limit="1" />
                </el-form-item>

                <el-form-item v-if="formData.main_type == 2" label="主长图" prop="main_url">
                    <div class="flex flex-col gap-1.5">
                        <material-picker type="image" v-model="formData.main_url" :limit="1" />
                        <p class="flex items-center gap-1 text-xs font-medium text-[#f97316]">
                            💡 建议上传长图，方便小程序内上下滑动查看
                        </p>
                        image.png
                    </div>
                </el-form-item>
                <el-form-item label="教程类目">
                    <el-select v-model="formData.tutorial_category_id" placeholder="请选择教程类目" clearable>
                        <el-option
                            v-for="item in optionsData.cateLists"
                            :key="item.id"
                            :label="item.name"
                            :value="item.id" />
                    </el-select>
                </el-form-item>
                <el-form-item label="副标题配置" prop="sub_items">
                    <div class="flex w-full flex-col gap-2.5">
                        <div
                            v-for="(item, index) in formData.sub_items"
                            :key="index"
                            class="flex flex-col gap-2 rounded-lg border px-3 py-2.5"
                            :class="
                                subItemErrors[index] ? 'border-[#f56c6c] bg-[#fff5f5]' : 'border-[#e2e8f0] bg-[#f8fafc]'
                            ">
                            <div class="flex items-center gap-2">
                                <el-select
                                    v-model="item.type"
                                    size="small"
                                    style="width: 80px; flex-shrink: 0"
                                    @change="handleSubTypeChange(index)">
                                    <el-option label="视频" value="1" />
                                    <el-option label="图片" value="2" />
                                </el-select>
                                <el-input
                                    v-model="item.title"
                                    size="small"
                                    placeholder="请输入副标题（必填）"
                                    :class="{ 'is-error': subItemErrors[index]?.title }"
                                    class="flex-1"
                                    @input="clearSubItemError(index, 'title')" />
                            </div>

                            <p v-if="subItemErrors[index]?.title" class="text-xs text-[#f56c6c]">请输入副标题</p>

                            <div class="flex items-center gap-2">
                                <div class="flex flex-1 flex-wrap items-center gap-1.5">
                                    <material-picker
                                        :type="item.type == '1' ? 'video' : 'image'"
                                        v-model="item.url"
                                        :limit="1"
                                        :show-btn-text="item.url ? '重新上传' : item.type == '1' ? '传视频' : '传长图'"
                                        @change="clearSubItemError(index, 'url')" />
                                    <span v-if="item.type == '1'" class="whitespace-nowrap text-[11px] text-[#f97316]">
                                        💡 建议上传视频
                                    </span>
                                    <el-tag v-if="item.url" type="success" size="small">已就绪</el-tag>
                                    <el-tag v-else type="info" size="small">待上传</el-tag>
                                </div>

                                <el-button type="danger" link :icon="Delete" @click="removeSubItem(index)" />
                            </div>

                            <p v-if="subItemErrors[index]?.url" class="text-xs text-[#f56c6c]">
                                请上传{{ item.type == "1" ? "视频" : "长图" }}文件
                            </p>
                        </div>

                        <p v-if="subItemsEmptyError" class="text-xs text-[#f56c6c]">请至少添加一条副标题</p>

                        <div
                            v-if="formData.sub_items.length === 0"
                            class="rounded-lg border border-dashed py-3 text-center text-sm"
                            :class="
                                subItemsEmptyError
                                    ? 'border-[#f56c6c] text-[#f56c6c]'
                                    : 'border-[#e2e8f0] text-[#94a3b8]'
                            ">
                            暂无副标题，点击下方按钮添加
                        </div>

                        <el-button class="w-full" type="primary" plain size="small" :icon="Plus" @click="addSubItem">
                            添加副标题
                        </el-button>
                    </div>
                </el-form-item>

                <el-form-item label="排序" prop="sort">
                    <div>
                        <el-input class="ls-input" v-model="formData.sort" :min="0" :max="9999" />
                        <div class="form-tips">默认为0，数值越大排越前面</div>
                    </div>
                </el-form-item>
                <el-form-item label="状态" prop="sort">
                    <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>

<script setup lang="ts">
import {
    addTutorial,
    editTutorial,
    getTutorialCategoryLists,
    getTutorialDetail,
} from "@/api/ai_application/device/tutorial";
import { useLockFn } from "@/hooks/useLockFn";
import { type FormInstance } from "element-plus";
import { Delete, Plus } from "@element-plus/icons-vue";
import { useDictOptions } from "@/hooks/useDictOptions";
import { setFormData } from "@/utils/util";

interface SubItem {
    title: string;
    type: string;
    url: string;
}

interface SubItemError {
    title?: boolean;
    url?: boolean;
}

interface TutorialForm {
    id: string | undefined;
    title: string;
    main_type: 1 | 2;
    main_url: string;
    sub_items: SubItem[];
    tutorial_category_id: string | number;
    status: number;
    sort: number;
}

const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

const formRef = shallowRef<FormInstance>();
const popupRef = ref();
const mode = ref<"add" | "edit">("add");

const subItemErrors = ref<SubItemError[]>([]);
const subItemsEmptyError = ref(false);

const formData = reactive<TutorialForm>({
    id: undefined,
    title: "",
    main_type: 1,
    main_url: "",
    sub_items: [],
    tutorial_category_id: "",
    status: 1,
    sort: 0,
});

const rules = {
    title: [{ required: true, message: "请输入主标题" }],
    tutorial_category_id: [{ required: true, message: "请选择教程类目" }],
    main_type: [{ required: true, message: "请选择主内容类型" }],
    main_url: [
        { required: true, message: "请上传主内容视频或长图", trigger: "change" },
        {
            validator: (_: any, __: any, callback: any) => {
                if (formData.main_type == 1 && !formData.main_url) {
                    callback(new Error("请上传主视频"));
                } else if (formData.main_type == 2 && !formData.main_url) {
                    callback(new Error("请上传主长图"));
                } else {
                    callback();
                }
            },
            trigger: "change",
        },
    ],
};

const { optionsData } = useDictOptions<{
    cateLists: any[];
}>({
    cateLists: {
        api: getTutorialCategoryLists,
        params: {
            status: 1,
        },
        transformData: (data) => data.lists,
    },
});

const handleMainTypeChange = () => {
    formData.main_url = "";
};

const addSubItem = () => {
    formData.sub_items.push({
        title: "",
        type: "1",
        url: "",
    });
    subItemErrors.value.push({});
    subItemsEmptyError.value = false;
};

const handleSubTypeChange = (index: number) => {
    formData.sub_items[index].url = "";
    clearSubItemError(index, "url");
};

const removeSubItem = (index: number) => {
    formData.sub_items.splice(index, 1);
    subItemErrors.value.splice(index, 1);
};

const clearSubItemError = (index: number, field: keyof SubItemError) => {
    if (subItemErrors.value[index]) {
        subItemErrors.value[index][field] = false;
    }
};

const validateSubItems = (): boolean => {
    if (formData.sub_items.length === 0) {
        subItemsEmptyError.value = false;
        return true;
    }
    subItemsEmptyError.value = false;

    let valid = true;
    subItemErrors.value = formData.sub_items.map((item) => {
        const err: SubItemError = {};
        if (!item.title?.trim()) {
            err.title = true;
            valid = false;
        }
        if (!item.url?.trim()) {
            err.url = true;
            valid = false;
        }
        return err;
    });
    return valid;
};

const submit = async () => {
    await formRef.value?.validate();
    if (!validateSubItems()) return;
    mode.value === "add"
        ? await addTutorial({ ...formData, tutorial_category_id: formData.tutorial_category_id || 0 })
        : await editTutorial({ ...formData, category_id: formData.tutorial_category_id || 0 });
    close();
    emit("success");
};

const open = (type: "add" | "edit") => {
    popupRef.value?.open();
    mode.value = type;
    subItemErrors.value = [];
    subItemsEmptyError.value = false;
};

const close = () => {
    emit("close");
};

const { lockFn, isLock } = useLockFn(submit);

const getDetail = async (id: number) => {
    const data = await getTutorialDetail({ id });
    setFormData(data, formData);
    formData.tutorial_category_id = data.tutorial_category_id == 0 ? "" : data.tutorial_category_id;
};

defineExpose({ open, getDetail });
</script>
