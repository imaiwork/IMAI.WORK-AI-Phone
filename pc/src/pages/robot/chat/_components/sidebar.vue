<template>
    <div class="h-full flex gap-x-4">
        <div class="w-[210px] bg-white border-r border-r-[#e5e5e5] py-2 flex-shrink-0">
            <ElScrollbar>
                <ElMenu collapse :default-active="activeMenu" @select="handleMenuSelect" @open="handleMenuOpen">
                    <ElSubMenu v-for="(menu, key) in menuList" :index="`${menu.id}`">
                        <template #title>
                            <div class="flex gap-2 items-center">
                                <div class="w-6 h-6 flex-shrink-0 flex items-center justify-center">
                                    <img
                                        :src="menu.logo"
                                        class="w-[24px] h-[24px] rounded-full object-cover"
                                        v-if="menu.logo" />
                                </div>
                                <span class="text-base">{{ menu.name }}</span>
                            </div>
                        </template>
                        <ElSubMenu
                            v-for="(item, index) in menu.sub_list"
                            :title="item.name"
                            :index="`${item.pid}-${item.id}`">
                            <template #title>
                                <div class="flex gap-2 items-center overflow-hidden">
                                    <div class="text-ellipsis whitespace-nowrap overflow-hidden text-base">
                                        {{ item.name }}
                                    </div>
                                </div>
                            </template>
                            <ElMenuItem
                                :index="`${item.pid}-${item.id}-${subItem.id}`"
                                v-for="(subItem, subIndex) in item.sub_list">
                                <template #title>
                                    <div class="flex gap-2 items-center overflow-hidden">
                                        <div class="w-6 h-6 flex-shrink-0 flex items-center justify-center">
                                            <img
                                                :src="subItem.logo"
                                                class="w-[24px] h-[24px] rounded-full object-cover"
                                                v-if="subItem.logo" />
                                        </div>
                                        <div class="text-ellipsis whitespace-nowrap overflow-hidden text-base">
                                            {{ subItem.name }}
                                        </div>
                                    </div>
                                </template>
                            </ElMenuItem>
                        </ElSubMenu>
                    </ElSubMenu>
                </ElMenu>
            </ElScrollbar>
        </div>
    </div>
</template>

<script setup lang="ts">
import { type FormInstance } from "element-plus";
import { FieldEnum } from "@/components/form-designer/form-enums";
import FileUpload from "@/components/chatting/file-upload.vue";
import FileItem from "@/components/chatting/file-item.vue";
import { FileParams } from "@/composables/usePasteImage";
import { useUserStore } from "~/stores/user";
import { useAppStore } from "~/stores/app";
import { robotLists } from "@/api/robot";
import { TokensSceneEnum } from "@/enums/appEnums";

const route = useRoute();
const router = useRouter();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);
const getSceneTokens = userStore.getTokenByScene(TokensSceneEnum.SCENE_CHAT)?.score;

const { menuList } = useAppStore();
const activeMenu = ref<string>("");
const queryParams = reactive<any>({
    type: 3,
    page_size: 9999,
});

const scenePPid = ref<string>("");
const sceneId = ref<string | number>("");

const initMenu = () => {
    activeMenu.value = `${route.query.ppid}-${route.query.pid}-${route.query.id}`;
    sceneId.value = route.query.pid as string;
    scenePPid.value = route.query.ppid as string;
    getRobotLists();
};

const handleMenuOpen = (key: string) => {
    if (key.indexOf("-") > -1) {
        const [ppid, pid] = key.split("-");
        sceneId.value = pid;
        scenePPid.value = ppid;
        getRobotLists();
    }
};

const handleMenuSelect = (key: string) => {
    const [ppid, pid, id] = key.split("-");
    queryParams.ppid = ppid;
    queryParams.assistant_id = id;
    queryParams.scene_id = pid;
    router.replace({
        query: {
            ppid,
            pid,
            id,
        },
    });
    emit("change-scene");
};

const getRobotLists = async () => {
    if (!sceneId.value) return;
    const { lists } = await robotLists({
        ...queryParams,
        scene_id: sceneId.value,
    });
    menuList.forEach((item) => {
        if (item.id == scenePPid.value) {
            item.sub_list.forEach((subItem) => {
                if (subItem.id == sceneId.value) {
                    subItem.sub_list = lists;
                }
            });
        }
    });
};

const props = withDefaults(
    defineProps<{
        formList: any[];
        detail: Record<string, any>;
        isLock: boolean;
    }>(),
    {
        formList: () => [],
        detail: () => ({
            name: "",
            logo: "",
            description: "",
        }),
        isLock: false,
    }
);
const emit = defineEmits(["success", "change-scene"]);

const formData = reactive<any>({});
const formRef = shallowRef<FormInstance>();
const materialsMapKey = reactive({});

const fileLists = ref<FileParams[]>([]);
const fileLimit = ref(1);

const delFile = (index: number) => {
    fileLists.value.splice(index, 1);
};

const setFormData = () => {
    props.formList.forEach((item: any) => {
        formData[item.props.field] = "";
    });
};

const formValidate = async () => {
    return await formRef.value?.validate();
};

const submit = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    emit("success", formData);
};

watchEffect(() => {
    setFormData();
});

onMounted(() => {
    initMenu();
});

defineExpose({
    formData,
    fileLists,
    formValidate,
});
</script>
<style lang="scss" scoped>
.form-panel {
    :deep() {
        .el-textarea__inner,
        .el-input__wrapper,
        .el-select__wrapper {
            background-color: #f6f7f8;
        }
    }
}

:deep(.image-container) {
    width: 100% !important;
    height: 100% !important;
    .el-image__inner {
        object-fit: contain !important;
    }
}
:deep(.el-menu) {
    @apply border-none;
    .el-sub-menu__title,
    .el-menu-item {
        height: 52px !important;
    }
}
:deep(.el-form-item__label) {
    @apply text-base font-bold;
}
:deep(.el-menu--collapse) {
    width: 100%;
}
</style>
