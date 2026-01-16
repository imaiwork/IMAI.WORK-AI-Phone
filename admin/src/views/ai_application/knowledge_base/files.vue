<template>
    <div>
        <vector-files />
    </div>
</template>
<script lang="ts" setup>
import VectorFiles from "./components/vector-files.vue";
import RagFiles from "./components/rag-files.vue";

enum KnowledgeType {
    VECTOR = "vector",
    RAG = "rag",
}

const router = useRoute();
const knId = ref<string>();
const knType = ref<string>(KnowledgeType.VECTOR);

const handleTabClick = (tab: any) => {
    knType.value = tab.paneName;
};

onMounted(async () => {
    if (router.query.id) {
        knId.value = router.query.id as string;
        knType.value = router.query.type as string;
    }
});
</script>
