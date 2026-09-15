import React, { useEffect, useMemo, useRef, useState } from 'react';

const styles = `
:host { all: initial; }
* { box-sizing: border-box; font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, sans-serif; }
.launcher {
  position: fixed; right: 20px; bottom: 20px; z-index: 2147483000;
  width: 56px; height: 56px; border: 0; border-radius: 999px; color: #fff;
  cursor: pointer; box-shadow: 0 10px 30px rgba(15, 23, 42, .25);
}
.panel {
  position: fixed; right: 20px; bottom: 88px; z-index: 2147483000;
  width: min(380px, calc(100vw - 32px)); height: min(560px, calc(100vh - 120px));
  background: #fff; border-radius: 20px; overflow: hidden; display: flex; flex-direction: column;
  box-shadow: 0 20px 60px rgba(15, 23, 42, .25); border: 1px solid #e2e8f0;
}
.header { padding: 16px 18px; color: #fff; }
.header h3 { margin: 0; font-size: 15px; }
.header p { margin: 4px 0 0; font-size: 12px; opacity: .9; }
.messages { flex: 1; overflow-y: auto; padding: 16px; background: #f8fafc; }
.bubble { max-width: 88%; padding: 10px 12px; border-radius: 14px; font-size: 13px; line-height: 1.5; margin-bottom: 10px; white-space: pre-wrap; }
.bubble.user { margin-left: auto; background: var(--da-color); color: #fff; border-bottom-right-radius: 4px; }
.bubble.assistant { background: #fff; color: #0f172a; border: 1px solid #e2e8f0; border-bottom-left-radius: 4px; }
.citations { font-size: 11px; color: #64748b; margin-top: 6px; }
.composer { display: flex; gap: 8px; padding: 12px; border-top: 1px solid #e2e8f0; background: #fff; }
.composer input { flex: 1; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 12px; font-size: 13px; }
.composer button { border: 0; border-radius: 12px; padding: 0 14px; color: #fff; background: var(--da-color); cursor: pointer; font-weight: 600; }
.error { color: #e11d48; font-size: 12px; padding: 0 16px 8px; }
`;

export default function ChatWidget({ kbId, apiBase }) {
    const [open, setOpen] = useState(false);
    const [config, setConfig] = useState(null);
    const [input, setInput] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState('');
    const [sessionId, setSessionId] = useState(null);
    const [messages, setMessages] = useState([]);
    const listRef = useRef(null);
    const color = config?.primary_color || '#4f46e5';

    useEffect(() => {
        fetch(`${apiBase}/api/v1/widget/${kbId}`)
            .then(async (response) => {
                if (!response.ok) {
                    throw new Error('Unable to load widget configuration.');
                }
                return response.json();
            })
            .then((payload) => {
                setConfig(payload);
                setMessages([
                    { role: 'assistant', content: payload.welcome_message || 'Hi! How can I help?' },
                ]);
            })
            .catch((err) => setError(err.message));
    }, [apiBase, kbId]);

    useEffect(() => {
        listRef.current?.scrollTo(0, listRef.current.scrollHeight);
    }, [messages, open]);

    const send = async (event) => {
        event.preventDefault();
        const text = input.trim();
        if (!text || busy) {
            return;
        }

        setInput('');
        setError('');
        setBusy(true);
        setMessages((current) => [...current, { role: 'user', content: text }, { role: 'assistant', content: '' }]);

        try {
            const response = await fetch(`${apiBase}/api/v1/chat`, {
                method: 'POST',
                headers: {
                    Accept: 'text/event-stream',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    kb_id: kbId,
                    message: text,
                    session_id: sessionId,
                    stream: true,
                }),
            });

            if (!response.ok || !response.body) {
                const payload = await response.json().catch(() => ({}));
                throw new Error(payload.message || 'The assistant is unavailable right now.');
            }

            await readSse(response.body, {
                onMeta: (payload) => setSessionId(payload.session_id),
                onToken: (token) => {
                    setMessages((current) => {
                        const next = [...current];
                        const last = next[next.length - 1];
                        next[next.length - 1] = { ...last, content: (last.content || '') + token };
                        return next;
                    });
                },
                onDone: (payload) => {
                    if (payload.session_id) {
                        setSessionId(payload.session_id);
                    }
                    setMessages((current) => {
                        const next = [...current];
                        const last = next[next.length - 1];
                        next[next.length - 1] = { ...last, citations: payload.citations || [] };
                        return next;
                    });
                },
            });
        } catch (err) {
            setError(err.message);
            setMessages((current) => current.slice(0, -1));
        } finally {
            setBusy(false);
        }
    };

    const css = useMemo(() => styles, []);

    return (
        <>
            <style>{css}</style>
            <button className="launcher" style={{ background: color }} onClick={() => setOpen((value) => !value)} aria-label="Open DocAgent chat">
                {open ? '×' : '✦'}
            </button>
            {open && (
                <div className="panel" style={{ '--da-color': color }}>
                    <div className="header" style={{ background: color }}>
                        <h3>{config?.name || 'DocAgent'}</h3>
                        <p>Answers from this site's knowledge base</p>
                    </div>
                    <div className="messages" ref={listRef}>
                        {messages.map((message, index) => (
                            <div key={index} className={`bubble ${message.role}`}>
                                {message.content || (busy && index === messages.length - 1 ? '…' : '')}
                                {message.citations?.length > 0 && (
                                    <div className="citations">
                                        Sources: {message.citations.map((item) => item.document).join(', ')}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                    {error && <div className="error">{error}</div>}
                    <form className="composer" onSubmit={send}>
                        <input
                            value={input}
                            onChange={(event) => setInput(event.target.value)}
                            placeholder="Ask a question…"
                            disabled={busy}
                        />
                        <button type="submit" disabled={busy}>Send</button>
                    </form>
                </div>
            )}
        </>
    );
}

async function readSse(body, handlers) {
    const reader = body.getReader();
    const decoder = new TextDecoder();
    let buffer = '';
    let eventName = 'message';

    while (true) {
        const { done, value } = await reader.read();
        if (done) {
            break;
        }

        buffer += decoder.decode(value, { stream: true });
        const frames = buffer.split('\n\n');
        buffer = frames.pop() ?? '';

        for (const frame of frames) {
            let data = '';
            eventName = 'message';

            for (const line of frame.split('\n')) {
                if (line.startsWith('event:')) {
                    eventName = line.slice(6).trim();
                } else if (line.startsWith('data:')) {
                    data += line.slice(5).trim();
                }
            }

            if (!data) {
                continue;
            }

            const payload = JSON.parse(data);

            if (eventName === 'meta') {
                handlers.onMeta(payload);
            } else if (eventName === 'token') {
                handlers.onToken(payload.token || '');
            } else if (eventName === 'done') {
                handlers.onDone(payload);
            }
        }
    }
}
