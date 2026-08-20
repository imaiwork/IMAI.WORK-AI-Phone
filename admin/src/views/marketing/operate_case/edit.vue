<template>
    <popup
        ref="popupRef"
        async
        width="680px"
        :title="mode === 'add' ? '新增案例' : '编辑案例'"
        :confirm-loading="isLock"
        @confirm="lockFn"
        @close="close">
        <div>
            <el-form :model="formData" :rules="rules" ref="formRef" label-width="110px">
                <div class="mb-5 border-l-4 border-[#3b82f6] pl-3 text-sm font-medium text-[#111827]">基础信息</div>

                <el-form-item label="案例标题" prop="title">
                    <el-input v-model="formData.title" placeholder="例如：特色餐饮品牌全国招商" maxlength="100" />
                </el-form-item>

                <el-form-item label="所属分类" prop="category_type">
                    <el-select v-model="formData.category_type" placeholder="请选择分类" class="w-full">
                        <el-option label="本地生活" :value="1" />
                        <el-option label="个人IP" :value="2" />
                        <el-option label="企业服务" :value="3" />
                    </el-select>
                </el-form-item>

                <el-form-item label="列表页简述" prop="intro">
                    <el-input
                        v-model="formData.intro"
                        type="textarea"
                        :rows="2"
                        maxlength="500"
                        show-word-limit
                        placeholder="列表页展示的一段话介绍..." />
                </el-form-item>

                <el-divider />

                <div class="mb-5 border-l-4 border-[#3b82f6] pl-3 text-sm font-medium text-[#111827]">数据与标签</div>

                <el-form-item label="累计曝光量" prop="exposure">
                    <el-input v-model="formData.exposure" placeholder="请输入曝光量，如：10000+" />
                </el-form-item>

                <el-form-item label="精准线索数" prop="leads">
                    <el-input v-model="formData.leads" placeholder="请输入线索数，如：500+" />
                </el-form-item>

                <el-form-item label="意向客户数" prop="convert_users">
                    <el-input v-model="formData.convert_users" placeholder="请输入意向客户数（选填）" />
                </el-form-item>

                <el-form-item label="拓客人群" prop="target_users">
                    <div class="w-full">
                        <div
                            class="flex min-h-[42px] w-full flex-wrap items-center gap-2 rounded-md border border-[#d1d5db] bg-white p-2 transition-colors focus-within:border-[#3b82f6] focus-within:ring-1 focus-within:ring-[#3b82f6]">
                            <el-tag
                                v-for="(tag, index) in formData.target_users"
                                :key="index"
                                closable
                                size="small"
                                @close="removeTag(index)">
                                {{ tag }}
                            </el-tag>
                            <el-input
                                v-model="tagInput"
                                size="small"
                                placeholder="输入后回车添加标签..."
                                class="flex-1 border-none"
                                style="min-width: 120px"
                                @keyup.enter="addTag"
                                @blur="addTag" />
                        </div>
                        <p class="mt-1 text-xs text-[#9ca3af]">输入标签内容后按回车键确认添加</p>
                    </div>
                </el-form-item>

                <el-divider />

                <div class="mb-5 flex items-center justify-between">
                    <div class="border-l-4 border-[#3b82f6] pl-3 text-sm font-medium text-[#111827]">任务节点配置</div>
                    <el-button type="primary" plain size="small" :icon="Plus" @click="addTaskNode">
                        添加节点
                    </el-button>
                </div>

                <el-form-item prop="task_types" label-width="0">
                    <div class="w-full overflow-hidden rounded-lg border border-[#e5e7eb]">
                        <table class="w-full text-sm">
                            <thead class="bg-[#f3f4f6]">
                                <tr>
                                    <th
                                        class="w-14 px-3 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[#6b7280]">
                                        序号
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[#6b7280]">
                                        执行任务
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[#6b7280]">
                                        开始时间
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-left text-xs font-medium uppercase tracking-wider text-[#6b7280]">
                                        结束时间
                                    </th>
                                    <th
                                        class="w-14 px-3 py-2.5 text-right text-xs font-medium uppercase tracking-wider text-[#6b7280]">
                                        操作
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#e5e7eb] bg-white">
                                <tr v-for="(node, index) in formData.task_types" :key="node._key">
                                    <td class="px-3 py-3 text-xs font-medium text-[#6b7280]">
                                        {{ String(index + 1).padStart(2, "0") }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <el-select v-model="node.type" size="small" class="w-full" placeholder="请选择">
                                            <el-option label="私信接管" value="私信接管" />
                                            <el-option label="评论接管" value="评论接管" />
                                            <el-option label="微信接管" value="微信接管" />
                                            <el-option label="朋友圈接管" value="朋友圈接管" />
                                            <el-option
                                                label="小红书/抖音/视频号/快手/朋友圈发布"
                                                value="小红书/抖音/视频号/快手/朋友圈发布" />
                                            <el-option label="截流任务" value="截流任务" />
                                            <el-option label="触达任务" value="触达任务" />
                                            <el-option label="同城任务" value="同城任务" />
                                            <el-option label="团购截流" value="团购截流" />
                                            <el-option label="视频号获客" value="视频号获客" />
                                        </el-select>
                                    </td>
                                    <td class="px-3 py-3">
                                        <el-time-picker
                                            v-model="node.start_time"
                                            format="HH:mm"
                                            value-format="HH:mm"
                                            placeholder="开始"
                                            size="small"
                                            style="width: 100px" />
                                    </td>
                                    <td class="px-3 py-3">
                                        <el-time-picker
                                            v-model="node.end_time"
                                            format="HH:mm"
                                            value-format="HH:mm"
                                            placeholder="结束"
                                            size="small"
                                            style="width: 100px" />
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <el-button
                                            type="danger"
                                            link
                                            size="small"
                                            :icon="Delete"
                                            @click="removeTaskNode(index)" />
                                    </td>
                                </tr>
                                <tr v-if="formData.task_types.length === 0">
                                    <td colspan="5" class="py-6 text-center text-sm text-[#9ca3af]">
                                        暂无节点，点击右上角添加
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="taskNodesError" class="mt-1 text-xs text-[#f56c6c]">请至少添加一个任务节点</p>
                    <p v-if="taskNodeFieldError" class="mt-1 text-xs text-[#f56c6c]">
                        请完整填写所有节点的执行任务和执行时间区间
                    </p>
                </el-form-item>

                <el-divider />

                <div class="mb-5 border-l-4 border-[#3b82f6] pl-3 text-sm font-medium text-[#111827]">方案说明</div>

                <el-form-item label="适用场景" prop="detail_content">
                    <el-input
                        v-model="formData.detail_content"
                        type="textarea"
                        :rows="3"
                        maxlength="500"
                        show-word-limit
                        placeholder="描述该方案的适用场景..." />
                </el-form-item>

                <el-form-item label="目标人群" prop="detail_users">
                    <el-input
                        v-model="formData.detail_users"
                        type="textarea"
                        :rows="2"
                        maxlength="500"
                        show-word-limit
                        placeholder="描述目标人群..." />
                </el-form-item>

                <el-form-item label="执行动作" prop="detail_task_types">
                    <el-input
                        v-model="formData.detail_task_types"
                        type="textarea"
                        :rows="3"
                        maxlength="500"
                        show-word-limit
                        placeholder="描述执行动作..." />
                </el-form-item>

                <el-divider />

                <div class="mb-5 border-l-4 border-[#3b82f6] pl-3 text-sm font-medium text-[#111827]">媒体素材</div>

                <el-form-item label="转化截图" prop="detail_images">
                    <div class="flex flex-col gap-1.5 w-full">
                        <material-picker type="image" v-model="formData.detail_images" :limit="9" />
                        <p class="text-xs text-[#9ca3af]">建议尺寸比例 9:16，最多上传 9 张</p>
                    </div>
                </el-form-item>

                <el-form-item label="演示视频" prop="detail_videos">
                    <div class="flex flex-col gap-1.5 w-full">
                        <material-picker type="video" v-model="formData.detail_videos" :limit="1" />
                        <p class="text-xs text-[#9ca3af]">支持 MP4 格式，建议大小不超过 50M</p>
                    </div>
                </el-form-item>

                <el-form-item label="上架状态" prop="status">
                    <el-switch
                        v-model="formData.status"
                        :active-value="1"
                        :inactive-value="0"
                        active-text="上架"
                        inactive-text="下架" />
                </el-form-item>
            </el-form>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { addOperateCase, editOperateCase } from "@/api/marketing/operate_case";
import { useLockFn } from "@/hooks/useLockFn";
import { type FormInstance } from "element-plus";
import { Delete, Plus } from "@element-plus/icons-vue";

// ---- 常量：任务类型列表（与模板选项保持一致，取第一项作为默认值）----
const TASK_TYPE_OPTIONS = [
    "私信接管",
    "评论接管",
    "微信接管",
    "朋友圈接管",
    "小红书/抖音/视频号/快手/朋友圈发布",
    "截流任务",
    "触达任务",
    "同城任务",
    "团购截流",
    "视频号获客",
] as const;

const DEFAULT_TASK_TYPE = TASK_TYPE_OPTIONS[0]; // "私信接管"
const DEFAULT_START_TIME = "00:00";
const DEFAULT_END_TIME = "00:30";

// ---- 类型定义 ----
interface TaskNode {
    _key: number;
    type: string;
    start_time: string;
    end_time: string;
}

interface CaseForm {
    id: string;
    category_type: number | string;
    title: string;
    intro: string;
    exposure: string;
    leads: string;
    convert_users: string;
    target_users: string[];
    task_types: TaskNode[];
    detail_content: string;
    detail_users: string;
    detail_task_types: string;
    detail_images: string[];
    detail_videos: string[];
    status: number;
}

// ---- emits ----
const emit = defineEmits<{
    (e: "close"): void;
    (e: "success"): void;
}>();

// ---- refs ----
const formRef = shallowRef<FormInstance>();
const popupRef = ref();
const mode = ref<"add" | "edit">("add");
const tagInput = ref("");
const taskNodesError = ref(false);
const taskNodeFieldError = ref(false);

// ---- 表单数据 ----
const formData = reactive<CaseForm>({
    id: "",
    category_type: "",
    title: "",
    intro: "",
    exposure: "0",
    leads: "0",
    convert_users: "0",
    target_users: [],
    task_types: [],
    detail_content: "",
    detail_users: "",
    detail_task_types: "",
    detail_images: [],
    detail_videos: [],
    status: 1,
});

// ---- 校验规则 ----
const rules = {
    title: [{ required: true, message: "请输入案例标题", trigger: "blur" }],
    category_type: [{ required: true, message: "请选择所属分类", trigger: "change" }],
    exposure: [{ required: true, message: "请输入累计曝光量", trigger: "blur" }],
    leads: [{ required: true, message: "请输入精准线索数", trigger: "blur" }],
};

// ---- 标签操作 ----
const addTag = () => {
    const val = tagInput.value.trim();
    if (val && !formData.target_users.includes(val)) {
        formData.target_users.push(val);
        formRef.value?.validateField("target_users");
    }
    tagInput.value = "";
};

const removeTag = (index: number) => {
    formData.target_users.splice(index, 1);
    formRef.value?.validateField("target_users");
};

// ---- 任务节点操作 ----
const addTaskNode = () => {
    formData.task_types.push({
        _key: Date.now(),
        type: DEFAULT_TASK_TYPE, // 默认选中第一个选项
        start_time: DEFAULT_START_TIME, // 默认 00:00
        end_time: DEFAULT_END_TIME, // 默认 00:30
    });
    taskNodesError.value = false;
};

const removeTaskNode = (index: number) => {
    formData.task_types.splice(index, 1);
};

const validateTaskNodes = (): boolean => {
    taskNodesError.value = false;
    taskNodeFieldError.value = false;
    if (formData.task_types.length === 0) {
        taskNodesError.value = true;
        return false;
    }
    const hasEmpty = formData.task_types.some((n) => !n.type || !n.start_time || !n.end_time);
    if (hasEmpty) {
        taskNodeFieldError.value = true;
        return false;
    }
    return true;
};

// ---- 构建提交参数（对齐接口字段）----
const buildPayload = () => {
    const nodes = formData.task_types.map(({ type, start_time, end_time }) => ({
        type,
        time: `${start_time}-${end_time}`,
    }));

    return {
        ...(mode.value === "edit" ? { id: formData.id } : {}),
        category_type: formData.category_type,
        title: formData.title,
        intro: formData.intro,
        exposure: formData.exposure,
        leads: formData.leads,
        convert_users: formData.convert_users,
        target_users: formData.target_users,
        task_types: nodes,
        detail_content: formData.detail_content,
        detail_users: formData.detail_users,
        detail_task_types: formData.detail_task_types,
        detail_images: formData.detail_images,
        detail_videos: Array.isArray(formData.detail_videos) ? formData.detail_videos : [formData.detail_videos],
        status: formData.status,
    };
};

// ---- 提交 ----
const submit = async () => {
    await formRef.value?.validate();
    if (!validateTaskNodes()) {
        return Promise.reject("任务节点校验未通过");
    }
    const payload = buildPayload();
    mode.value === "add" ? await addOperateCase(payload) : await editOperateCase(payload);
    close();
    emit("success");
};

// ---- 重置表单 ----
const resetForm = () => {
    formData.id = "";
    formData.category_type = "";
    formData.title = "";
    formData.intro = "";
    formData.exposure = "0";
    formData.leads = "0";
    formData.convert_users = "0";
    formData.target_users = [];
    formData.task_types = [];
    formData.detail_content = "";
    formData.detail_users = "";
    formData.detail_task_types = "";
    formData.detail_images = [];
    formData.detail_videos = [];
    formData.status = 1;
    tagInput.value = "";
    taskNodesError.value = false;
    taskNodeFieldError.value = false;
    formRef.value?.clearValidate();
};

// ---- 打开 / 关闭 ----
const open = (type: "add" | "edit") => {
    resetForm();
    popupRef.value?.open();
    mode.value = type;
};

const close = () => {
    emit("close");
};

// ---- 回显数据（编辑时调用）----
const setFormData = (data: any) => {
    resetForm();
    for (const key in formData) {
        if (data[key] != null) {
            // @ts-ignore
            formData[key] = data[key];
        }
    }
    if (Array.isArray(data.task_types)) {
        formData.task_types = data.task_types.map((node: any) => {
            const [start_time = DEFAULT_START_TIME, end_time = DEFAULT_END_TIME] = (node.time ?? "").split("-");
            return {
                _key: Date.now() + Math.random(),
                type: node.type ?? DEFAULT_TASK_TYPE,
                start_time,
                end_time,
            };
        });
    }
    if (Array.isArray(data.detail_videos)) {
        formData.detail_videos = Array.isArray(data.detail_videos) ? data.detail_videos[0] : data.detail_videos;
    }
};

const { lockFn, isLock } = useLockFn(submit);

defineExpose({ open, setFormData });
</script>

<style scoped></style>
