import { generateHTML } from '@tiptap/html'
import StarterKit from '@tiptap/starter-kit'

const json = {"type":"doc","content":[{"type":"heading","attrs":{"level":2},"content":[{"type":"text","text":"1. Memahami Tipe Tiket (Issue Types)"}]}]};

console.log(generateHTML(json, [StarterKit]));
