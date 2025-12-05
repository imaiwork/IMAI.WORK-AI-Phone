import useAccount from "./useAccount";

const friendPager = reactive<Record<string, any>>({
    friendList: [],
    currentFriend: {},
    friendListLoading: false,
});

export default function useFriends() {
    const { currentAccount } = useAccount();

    const getFriendList = async () => {
        friendPager.friendListLoading = true;
    };

    watch(
        () => currentAccount.value,
        () => {},
        { deep: true }
    );

    return {
        friendPager,
        getFriendList,
    };
}
