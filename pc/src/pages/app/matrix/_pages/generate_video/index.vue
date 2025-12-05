<template>
	<div class="h-full flex flex-col bg-app-bg-2 rounded-[20px]">
		<div
			class="flex-shrink-0 px-[14px] border-[0] border-b-[1px] border-app-border-1">
			<ElScrollbar>
				<div class="flex items-center justify-end h-[88px]">
					<div class="flex items-center gap-[14px]">
						<ElInput
							v-model="queryParams.name"
							prefix-icon="el-icon-Search"
							class="!w-[240px] search-name-input"
							placeholder="请输入名称"
							clearable
							@clear="resetPage()"
							@keydown.enter="resetPage()">
							<template #append>
								<ElButton text @click="resetPage()">
									搜索
								</ElButton>
							</template>
						</ElInput>
						<ElTooltip content="刷新">
							<ElButton
								circle
								color="#1f1f1f"
								icon="el-icon-Refresh"
								class="!w-10 !h-10"
								@click="resetPage()"></ElButton>
						</ElTooltip>
					</div>
				</div>
			</ElScrollbar>
		</div>
		<div
			class="grow min-h-0 overflow-y-auto flex flex-col dynamic-scroller"
			:infinite-scroll-immediate="false"
			:infinite-scroll-disabled="!pager.isLoad"
			:infinite-scroll-distance="10"
			v-infinite-scroll="load">
			<div class="h-full p-4" v-loading="pager.loading">
				<div v-if="pager.lists.length">
					<div
						class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4">
						<div
							v-for="(item, index) in pager.lists"
							class="material-item group"
							:key="index">
							<video-card
								:item="item"
								@edit="handleEdit"
								@delete="handleDelete"
								@preview="handlePreviewVideo"></video-card>
						</div>
					</div>
					<div
						v-if="!pager.isLoad"
						class="text-white text-center text-xs w-full py-4">
						暂无更多了~
					</div>
				</div>
				<div class="h-full flex items-center justify-center" v-else>
					<ElEmpty />
				</div>
			</div>
		</div>
	</div>
	<edit-popup
		v-if="showEditPopup"
		ref="editPopupRef"
		@close="showEditPopup = false"
		@success="resetPage()" />
	<preview-video
		v-if="showPreviewVideo"
		ref="previewVideoRef"
		@close="showPreviewVideo = false" />
</template>

<script setup lang="ts">
import { getDigitalHumanVideo, deleteDigitalHumanVideo } from "@/api/redbook";
import EditPopup from "./_components/edit.vue";
import VideoCard from "../../_components/dh-video-card.vue";

const queryParams = reactive({
	name: "",
	page_no: 1,
});

const { pager, getLists, resetPage } = usePaging({
	fetchFun: getDigitalHumanVideo,
	params: queryParams,
	isScroll: true,
});

const load = () => {
	queryParams.page_no++;
	getLists();
};

const showEditPopup = ref(false);
const editPopupRef = ref<InstanceType<typeof EditPopup>>();

const handleEdit = async (item: any) => {
	showEditPopup.value = true;
	await nextTick();
	editPopupRef.value.open();
	editPopupRef.value.setFormData(item);
};

const handleDelete = async ({ id }: any) => {
	try {
		await deleteDigitalHumanVideo({ id });
		const index = pager.lists.findIndex((item) => item.id === id);
		pager.lists.splice(index, 1);
	} catch (error) {
		feedback.msgWarning(error);
	}
};

const previewVideoRef = ref();
const showPreviewVideo = ref(false);
const handlePreviewVideo = async (url: string) => {
	showPreviewVideo.value = true;
	await nextTick();
	previewVideoRef.value?.open();
	previewVideoRef.value?.setUrl(url);
};

getLists();
</script>

<style scoped lang="scss">
.material-item {
	@apply flex gap-x-4 h-[288px] relative overflow-hidden border border-[#ffffff33] rounded-xl cursor-pointer;
	&::after {
		@apply absolute top-0 left-0 w-full h-full;
		content: "";
		background: linear-gradient(180deg, rgba(0, 0, 0, 0) 50%, #000 100%);
		pointer-events: none;
	}
}
</style>
