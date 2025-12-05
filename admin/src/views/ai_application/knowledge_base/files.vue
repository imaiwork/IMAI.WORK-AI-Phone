<template>
    <div>
        <el-tabs v-model="knType" @tab-click="handleTabClick" v-if="!knId">
            <el-tab-pane label="向量知识库" :name="KnowledgeType.VECTOR"></el-tab-pane>
            <el-tab-pane label="RAG知识库" :name="KnowledgeType.RAG"></el-tab-pane>
        </el-tabs>
        <rag-files v-if="knType == KnowledgeType.RAG" />
        <vector-files v-if="knType == KnowledgeType.VECTOR" />
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
