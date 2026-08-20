<template>
    <popup ref="popupRef" :title="title" async width="640px" @confirm="handleSave" @close="close">
        <el-form ref="formRef" :model="formData" :rules="rules" label-width="140px" class="ls-form">
            <el-form-item label="等级名称" prop="level_name">
                <el-input v-model="formData.level_name" maxlength="30" placeholder="如:体验会员 / 标准会员 / 年度尊享" class="w-[360px]" />
            </el-form-item>
            <el-form-item label="排序">
                <el-input-number v-model="formData.sort" :min="0" :max="9999" />
                <span class="text-xs text-gray-400 ml-2">数值越大越靠前</span>
            </el-form-item>

            <el-divider content-position="left">算力赠送</el-divider>
            <el-form-item label="赠送算力">
                <el-input-number v-model="formData.grant_tokens" :min="0" :max="999999" />
                <span class="text-xs text-gray-400 ml-2">每个周期发放的算力数量</span>
            </el-form-item>
            <el-form-item label="赠送周期">
                <el-radio-group v-model="formData.grant_cycle">
                    <el-radio :value="0">不赠送</el-radio>
                    <el-radio :value="1">每日</el-radio>
                    <el-radio :value="2">每月</el-radio>
                    <el-radio :value="3">每年</el-radio>
                </el-radio-group>
            </el-form-item>

            <el-divider content-position="left">创建上限</el-divider>
            <el-form-item label="智能体数量">
                <quota-input v-model="formData.max_robots" />
            </el-form-item>
            <el-form-item label="知识库数量">
                <quota-input v-model="formData.max_knowledges" />
            </el-form-item>
            <el-form-item label="人设数量">
                <quota-input v-model="formData.max_personas" />
            </el-form-item>
            <el-form-item label="绑定手机上限">
                <quota-input v-model="formData.max_mobiles" />
            </el-form-item>
            <el-form-item label="数字人克隆数">
                <quota-input v-model="formData.max_digital_humans" />
            </el-form-item>
            <el-form-item label="音色克隆数">
                <quota-input v-model="formData.max_voices" />
            </el-form-item>

            <el-divider content-position="left">可用模型</el-divider>
            <el-form-item label="允许的模型">
                <el-select
                    v-model="formData.allowed_models"
                    multiple
                    filterable
                    placeholder="选择模型(读自系统模型库)"
                    class="w-[420px]"
                    :loading="modelLoading">
                    <el-option
                        v-for="m in chatModels"
                        :key="m.id"
                        :label="m.name"
                        :value="String(m.id)">
                        <div class="flex items-center gap-2">
                            <img v-if="m.logo" :src="m.logo" class="w-5 h-5 rounded" />
                            <span>{{ m.name }}</span>
                            <el-tag v-if="!m.is_enable" type="info" size="small">停用</el-tag>
                        </div>
                    </el-option>
                </el-select>
                <div class="text-xs text-gray-400 mt-1">
                    留空表示全部模型可用 ·
                    <span class="text-blue-500 cursor-pointer hover:underline" @click="loadChatModels">刷新</span>
                </div>
            </el-form-item>

            <el-form-item label="状态">
                <el-switch v-model="formData.status" :active-value="1" :inactive-value="0" />
            </el-form-item>
        </el-form>
    </popup>
</template>

<script setup lang="ts">
import type { FormInstance } from "element-plus";
import { addUserLevel, editUserLevel } from "@/api/consumer";
import { getAiModel } from "@/api/ai_setting/model";
import { setFormData } from "@/utils/util";
import QuotaInput from "./quota-input.vue";

const emit = defineEmits(["success", "close"]);

// 从系统大模型库实时拉取(chat 类),保持跟「AI 设置 → AI 模型」一致
const chatModels = ref<any[]>([]);
const modelLoading = ref(false);
const loadChatModels = async () => {
    try {
        modelLoading.value = true;
        const res: any = await getAiModel();
        // 后端返回 { chatModels: [...], drawModels: [...], humanModels: [...] }
        chatModels.value = Array.isArray(res?.chatModels) ? res.chatModels : (Array.isArray(res?.chat) ? res.chat : []);
    } catch (e) {
        console.warn("[member-level] 加载模型库失败:", e);
        chatModels.value = [];
    } finally {
        modelLoading.value = false;
    }
};
loadChatModels();

const popupRef = ref();
const formRef = shallowRef<FormInstance>();
const mode = ref<"add" | "edit">("add");
const title = computed(() => (mode.value === "edit" ? "编辑会员等级" : "新增会员等级"));

const formData = reactive<any>({
    id: 0,
    level_name: "",
    sort: 0,
    grant_tokens: 0,
    grant_cycle: 0,
    max_robots: 0,
    max_knowledges: 0,
    max_personas: 0,
    max_mobiles: 0,
    max_digital_humans: 0,
    max_voices: 0,
    /** 表单内为模型 id 字符串数组；提交时转成 { id: name } */
    allowed_models: [] as string[],
    status: 1,
});

const rules = {
    level_name: [{ required: true, message: "请输入等级名称", trigger: "blur" }],
};

/** 兼容 { id: name } | id[] | name[] | JSON 字符串 → 表单单选用 id[] */
const normalizeAllowedModelIds = (v: any): string[] => {
    if (!v) return [];
    if (typeof v === "string") {
        try {
            return normalizeAllowedModelIds(JSON.parse(v));
        } catch {
            return [];
        }
    }
    let raw: string[] = [];
    if (Array.isArray(v)) {
        raw = v
            .map((item) => {
                if (item == null || item === "") return "";
                if (typeof item === "object") return String(item.id ?? item.model_id ?? "");
                return String(item);
            })
            .filter(Boolean);
    } else if (typeof v === "object") {
        raw = Object.keys(v).map(String).filter(Boolean);
    }
    // 历史数据可能存的是 name：尽量映射回模型 id（找不到则原样保留）
    const byId = new Set(chatModels.value.map((m) => String(m.id)));
    const byName = new Map(chatModels.value.map((m) => [String(m.name), String(m.id)]));
    return raw.map((item) => (byId.has(item) ? item : byName.get(item) || item));
};

/** 提交：id[] → { id: name }，与列表接口数据结构对齐 */
const buildAllowedModelsPayload = (ids: string[]) => {
    const map: Record<string, string> = {};
    const modelMap = new Map(chatModels.value.map((m) => [String(m.id), String(m.name || "")]));
    ids.forEach((id) => {
        const key = String(id);
        map[key] = modelMap.get(key) || key;
    });
    return map;
};

const handleSave = async () => {
    await formRef.value?.validate();
    const payload = {
        ...formData,
        grant_tokens: Number(formData.grant_tokens) || 0,
        allowed_models: buildAllowedModelsPayload(
            Array.isArray(formData.allowed_models) ? formData.allowed_models : [],
        ),
    };
    if (mode.value === "edit") {
        await editUserLevel(payload);
    } else {
        await addUserLevel(payload);
    }
    emit("success");
    close();
};

const open = (type: "add" | "edit") => {
    mode.value = type;
    popupRef.value?.open();
};
const close = () => emit("close");

defineExpose({
    open,
    close,
    setFormData: async (data: any) => {
        if (!chatModels.value.length) {
            await loadChatModels();
        }
        setFormData(data, formData);
        formData.grant_tokens = Number(formData.grant_tokens) || 0;
        // 接口返回 { id: name }，表单多选绑定 id[]
        formData.allowed_models = normalizeAllowedModelIds(formData.allowed_models);
    },
});
</script>
