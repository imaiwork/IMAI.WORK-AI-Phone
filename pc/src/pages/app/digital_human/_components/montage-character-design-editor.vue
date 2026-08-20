<template>
    <div class="w-full h-full">
        <div class="flex justify-between items-center mb-4 px-2">
            <h4 class="text-[14px] font-medium text-slate-700">人设设定</h4>
            <button @click="openChooseCharacter" class="text-primary text-xs font-black hover:underline">
                历史人设
            </button>
        </div>
        <div class="space-y-4">
            <ElInput v-model="personName" placeholder="人物名称 (如: 资深分析师)" class="custom-input !h-11" />
            <ElInput
                v-model="personIntroduction"
                type="textarea"
                :rows="3"
                placeholder="简述人物背景及..."
                class="custom-textarea"
                resize="none" />
        </div>
    </div>
    <choose-character
        v-if="showChooseCharacter"
        ref="chooseCharacterRef"
        @select="handleSelectCharacter"
        @close="showChooseCharacter = false" />
</template>

<script setup lang="ts">
import ChooseCharacter from "@/pages/app/digital_human/_components/choose-character.vue";

// ─── Props / Emits ───────────────────────────────────────────
interface Props {
    personName: string;
    personIntroduction: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    (e: "update:personName", val: string): void;
    (e: "update:personIntroduction", val: string): void;
}>();

// ─── 双向绑定计算属性 ──────────────────────────────────────────
const personName = computed({
    get: () => props.personName,
    set: (val) => emit("update:personName", val),
});

const personIntroduction = computed({
    get: () => props.personIntroduction,
    set: (val) => emit("update:personIntroduction", val),
});

// ─── 历史人设弹窗 ─────────────────────────────────────────────
const showChooseCharacter = ref(false);
const chooseCharacterRef = shallowRef<InstanceType<typeof ChooseCharacter>>();

const openChooseCharacter = async () => {
    showChooseCharacter.value = true;
    await nextTick();
    chooseCharacterRef.value?.open();
};

const handleSelectCharacter = ({ name, introduced }: { name: string; introduced: string }) => {
    emit("update:personName", name);
    emit("update:personIntroduction", introduced);
};
</script>
