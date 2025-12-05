<template>
    <ModelForm
        v-loading="loading"
        v-model="formData"
        :header-title="$route.meta.title || '编辑创作助手'"
        @submit="handelSubmit" />
</template>

<script setup lang="ts" name="aiaAssistantModelEdit">
import { assistantModelDetail, assistantModelAdd, assistantModelEdit } from "@/api/ai_assistant/model";
import ModelForm from "./components/model-form.vue";

const router = useRouter();
const route = useRoute();
const formData = reactive({
    id: "",
    logo: "",
    name: "",
    description: "",
    instructions: "",
    preliminary_ask: [
        {
            value: "",
        },
    ],
    template_info: {
        form: [], // 填写表单
    },
    form_info: "",
    scene_id: "",
    status: 1,
    sort: 1,
});

const handelSubmit = async () => {
    const params = {
        ...formData,
        scene_id:
            formData.scene_id && formData.scene_id.length > 0 ? formData.scene_id[formData.scene_id.length - 1] : "",
        template_info: JSON.stringify(formData.template_info),
        preliminary_ask: JSON.stringify(formData.preliminary_ask),
    };
    formData.id ? await assistantModelEdit(params) : await assistantModelAdd(params);
    router.back();
};

const loading = ref(false);
const getDetail = async () => {
    loading.value = true;
    try {
        const data = await assistantModelDetail({
            id: route.query.id,
        });
        data.preliminary_ask = JSON.parse(data.preliminary_ask);
        data.template_info = JSON.parse(data.template_info);
        setFormData(data);
    } finally {
        loading.value = false;
    }
};

const setFormData = (data: Record<any, any>) => {
    for (const key in formData) {
        if (data[key] != null && data[key] != undefined) {
            //@ts-ignore
            formData[key] = data[key];
        }
    }
};

onMounted(() => {
    if (route.query.id) {
        getDetail();
    }
});
</script>
