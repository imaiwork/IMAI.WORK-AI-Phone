<template>
	<div class="w-full h-full flex flex-col">
		<div v-for="(friends, letter, index) in filteredFriends" :key="letter">
			<div class="px-4 text-[#BDBDBD] font-bold my-1">{{ letter }}</div>
			<div
				v-for="friend in friends"
				:key="friend.id"
				class="px-4 hover:bg-[#D3D3D3] cursor-pointer"
				:class="{ 'bg-[#D3D3D3]': activeFriend?.id === friend.id }"
				@click="handleClickFriend(friend)">
				<div class="flex items-center h-[56px]">
					<img
						src="https://img.alicdn.com/imgextra/i1/O1CN01EI93PS1xWbnJ87dXX_!!6000000006451-2-tps-150-150.png"
						alt="avatar"
						class="w-8 h-8 rounded-full mr-2" />
					<span>{{ friend.name }}</span>
				</div>
			</div>
			<ElDivider class="!my-2" />
		</div>
	</div>
</template>

<script setup lang="ts">
interface Friend {
	id: number;
	name: string;
	avatar: string;
}

const search = ref("");
const activeFriend = ref<Friend | null>(null);
const friends = ref<Friend[]>([
	{ id: 1, name: "Alice", avatar: "path/to/avatar1.jpg" },
	{ id: 2, name: "Bob", avatar: "path/to/avatar2.jpg" },
	{ id: 3, name: "Charlie", avatar: "path/to/avatar3.jpg" },
	{ id: 4, name: "Chare", avatar: "path/to/avatar3.jpg" },
	// Add more friends here
]);

const filteredFriends = computed(() => {
	const groupedFriends: Record<string, Friend[]> = {};
	friends.value.forEach((friend) => {
		const firstLetter = friend.name.charAt(0).toUpperCase();
		if (!groupedFriends[firstLetter]) groupedFriends[firstLetter] = [];
		if (friend.name.includes(search.value))
			groupedFriends[firstLetter].push(friend);
	});
	return groupedFriends;
});

const searchFriend = () => {
	console.log(search.value);
};

const handleClickFriend = (friend: Friend) => {
	activeFriend.value = friend;
};
</script>

<style scoped lang="scss"></style>
