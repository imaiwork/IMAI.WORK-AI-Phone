export function useCallbacks<T>() {
  let handlers: T[] = []

  function add(handler: T) {
    handlers.push(handler)
    return () => {
      remove(handler)
    }
  }
  function reset() {
    handlers = []
  }

  function remove(handler: T) {
    const i = handlers.indexOf(handler)
    if (i > -1) handlers.splice(i, 1)
  }

  return {
    add,
    list: () => handlers,
    reset,
    remove
  }
}
