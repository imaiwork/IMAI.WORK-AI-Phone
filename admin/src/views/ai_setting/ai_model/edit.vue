<template>
    <div v-loading="isLock">
        <AiModelForm ref="formRef" v-model="formData" :header-title="$route.meta.title" :current-id="id" :type="type" />
        <footer-btns>
            <el-button type="primary" @click="handleSave">保存</el-button>
        </footer-btns>
    </div>
</template>

<script setup lang="ts" name="aiModelEdit">
import useTabsStore from "@/stores/modules/multipleTabs";
import AiModelForm from "./components/form.vue";
import { getAiModelDetail, editModel } from "@/api/ai_setting/model";
import { useLockFn } from "@/hooks/useLockFn";
import { setFormData } from "@/utils/util";
const router = useRouter();
const route = useRoute();
const type = route.query.type as string;
const id = route.query.id as string;
const tabsStore = useTabsStore();
const formRef = shallowRef();
const formData = reactive({
    id: "",
    name: "",
    logo: "",
    is_enable: 1,
});

const handleSave = async () => {
    await formRef.value.validate();
    await editModel({ ...formData, type });
    setTimeout(() => {
        router.back();
        tabsStore.removeTab(route.fullPath, router);
    });
};

const { lockFn, isLock } = useLockFn(handleSave);

const getDetails = async () => {
    const res = await getAiModelDetail({
        id: id,
    });
    setFormData(res, formData);
};
getDetails();
</script>
