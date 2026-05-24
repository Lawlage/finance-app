import { describe, it, expect, vi } from 'vitest'
import { renderComponent, screen, fireEvent } from '@/test/utils'
import FileDropzone from './FileDropzone'

describe('FileDropzone', () => {
    it('renders the default drop zone message', () => {
        renderComponent(
            <FileDropzone onFileSelect={vi.fn()} accept=".pdf,.csv" />,
        )

        expect(
            screen.getByText(
                'Drag and drop a bank statement, or click to browse',
            ),
        ).toBeInTheDocument()
        expect(
            screen.getByText('Supports PDF and CSV files (max 10MB)'),
        ).toBeInTheDocument()
    })

    it('calls onFileSelect and shows file name when a file is selected via input', () => {
        const onFileSelect = vi.fn()
        renderComponent(
            <FileDropzone onFileSelect={onFileSelect} accept=".pdf,.csv" />,
        )

        const input = document.querySelector(
            'input[type="file"]',
        ) as HTMLInputElement
        const file = new File(['test content'], 'statement.csv', {
            type: 'text/csv',
        })

        fireEvent.change(input, { target: { files: [file] } })

        expect(onFileSelect).toHaveBeenCalledWith(file)
        expect(screen.getByText(/statement\.csv/)).toBeInTheDocument()
    })

    it('calls onFileSelect when a file is dropped', () => {
        const onFileSelect = vi.fn()
        renderComponent(
            <FileDropzone onFileSelect={onFileSelect} accept=".pdf,.csv" />,
        )

        const dropzone = screen
            .getByText('Drag and drop a bank statement, or click to browse')
            .closest('div[class*="cursor-pointer"]') as HTMLElement

        const file = new File(['test content'], 'statement.pdf', {
            type: 'application/pdf',
        })

        fireEvent.drop(dropzone, {
            dataTransfer: { files: [file] },
        })

        expect(onFileSelect).toHaveBeenCalledWith(file)
    })

    it('applies drag styling on drag over', () => {
        renderComponent(
            <FileDropzone onFileSelect={vi.fn()} accept=".pdf,.csv" />,
        )

        const dropzone = screen
            .getByText('Drag and drop a bank statement, or click to browse')
            .closest('div[class*="cursor-pointer"]') as HTMLElement

        fireEvent.dragOver(dropzone, {
            dataTransfer: { files: [] },
        })

        expect(dropzone).toHaveClass('border-indigo-500')
    })

    it('removes drag styling on drag leave', () => {
        renderComponent(
            <FileDropzone onFileSelect={vi.fn()} accept=".pdf,.csv" />,
        )

        const dropzone = screen
            .getByText('Drag and drop a bank statement, or click to browse')
            .closest('div[class*="cursor-pointer"]') as HTMLElement

        fireEvent.dragOver(dropzone, {
            dataTransfer: { files: [] },
        })
        fireEvent.dragLeave(dropzone)

        expect(dropzone).not.toHaveClass('border-indigo-500')
    })

    it('sets the correct accept attribute on the file input', () => {
        renderComponent(
            <FileDropzone onFileSelect={vi.fn()} accept=".pdf,.csv" />,
        )

        const input = document.querySelector(
            'input[type="file"]',
        ) as HTMLInputElement
        expect(input).toHaveAttribute('accept', '.pdf,.csv')
    })

    it('clicks the hidden file input when the dropzone is clicked', () => {
        renderComponent(
            <FileDropzone onFileSelect={vi.fn()} accept=".pdf,.csv" />,
        )

        const input = document.querySelector(
            'input[type="file"]',
        ) as HTMLInputElement
        const clickSpy = vi.spyOn(input, 'click')

        const dropzone = screen
            .getByText('Drag and drop a bank statement, or click to browse')
            .closest('div[class*="cursor-pointer"]') as HTMLElement

        fireEvent.click(dropzone)

        expect(clickSpy).toHaveBeenCalled()
    })
})
