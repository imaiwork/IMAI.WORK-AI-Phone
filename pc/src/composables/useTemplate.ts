export default function useTemplate() {
    let headerRender;
    const DefineTemplate = {
        setup(_, { slots }) {
            return () => {
                headerRender = slots.default;
            };
        },
    };

    const UseTemplate = (props: any) => {
        return headerRender(props);
    };
    return {
        DefineTemplate,
        UseTemplate,
    };
}
