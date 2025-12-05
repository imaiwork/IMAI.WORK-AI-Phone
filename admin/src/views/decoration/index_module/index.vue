<template>
	<div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
		<el-card
			class="!border-none mb-4"
			shadow="never"
			v-for="item in indexConfig">
			<template #header>
				<div class="flex items-center justify-between">
					<div class="text-lg font-bold">{{ item.name }}</div>
				</div>
			</template>
			<div class="">
				<el-card
					class="!border-none mb-4"
					v-for="(data, index) in item.lists">
					<div class="flex justify-between items-center">
						<div class="flex items-center gap-2">
							<img :src="data.pic" class="w-[48px] h-[48px]" />
							<span>{{ data.name }}</span>
						</div>
						<el-button
							type="primary"
							@click="handleEdit(data, item.type, index)"
							>点击设置</el-button
						>
					</div>
				</el-card>
			</div>
		</el-card>
	</div>
	<edit-popup
		ref="editPopupRef"
		v-if="showEdit"
		@close="showEdit = false"
		@success="handleSubmit" />
</template>

<script setup lang="ts">
import { saveConfig } from "@/api/app";
import useAppStore from "@/stores/modules/app";
import EditPopup from "./edit.vue";
import feedback from "@/utils/feedback";

const appStore = useAppStore();
const getIndexConfig = computed(() => appStore.config.index_config);

const indexConfig = ref<any[]>(getIndexConfig.value);

const showEdit = ref(false);
const editPopupRef = shallowRef<InstanceType<typeof EditPopup>>();

const state = reactive<Record<string, any>>({
	index: "",
	type: "",
});

const handleEdit = async (data: any, type: string, index: number) => {
	showEdit.value = true;
	await nextTick();
	state.index = index;
	state.type = type;
	editPopupRef.value?.setFormData(data);
	editPopupRef.value?.open();
};

const handleSubmit = async (result: any) => {
	const { type, index } = state;
	indexConfig.value.forEach((item) => {
		if (item.type === type) {
			item.lists[index] = result;
		}
	});
	feedback.loading("保存中...");
	try {
		await saveConfig({
			type: "index",
			name: "config",
			data: indexConfig.value,
		});
	} finally {
		feedback.closeLoading();
	}
};
</script>

<style scoped></style>
