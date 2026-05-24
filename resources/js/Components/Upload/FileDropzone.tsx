import { type ChangeEvent, type DragEvent, useRef, useState } from 'react'

interface FileDropzoneProps {
    onFileSelect: (file: File) => void
    accept: string
}

export default function FileDropzone({
    onFileSelect,
    accept,
}: FileDropzoneProps) {
    const [isDragging, setIsDragging] = useState(false)
    const [selectedFile, setSelectedFile] = useState<File | null>(null)
    const inputRef = useRef<HTMLInputElement>(null)

    function handleDrop(e: DragEvent<HTMLDivElement>) {
        e.preventDefault()
        setIsDragging(false)
        if (e.dataTransfer.files.length > 0) {
            const file = e.dataTransfer.files[0]
            setSelectedFile(file)
            onFileSelect(file)
        }
    }

    function handleChange(e: ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0]
        if (file) {
            setSelectedFile(file)
            onFileSelect(file)
        }
    }

    return (
        <div
            onDragOver={(e) => {
                e.preventDefault()
                setIsDragging(true)
            }}
            onDragLeave={() => {
                setIsDragging(false)
            }}
            onDrop={handleDrop}
            onClick={() => inputRef.current?.click()}
            className={`cursor-pointer rounded-lg border-2 border-dashed p-8 text-center transition-colors ${
                isDragging
                    ? 'border-indigo-500 bg-indigo-50'
                    : 'border-gray-300 hover:border-gray-400'
            }`}
        >
            <input
                ref={inputRef}
                type="file"
                accept={accept}
                onChange={handleChange}
                className="hidden"
            />
            {selectedFile ? (
                <p className="text-sm text-gray-900">
                    Selected: <strong>{selectedFile.name}</strong> (
                    {(selectedFile.size / 1024).toFixed(1)} KB)
                </p>
            ) : (
                <div>
                    <p className="text-sm text-gray-600">
                        Drag and drop a bank statement, or click to browse
                    </p>
                    <p className="mt-1 text-xs text-gray-400">
                        Supports PDF and CSV files (max 10MB)
                    </p>
                </div>
            )}
        </div>
    )
}
