#!/usr/bin/env python3
"""Fetch YouTube transcript. Usage: python3 fetch_transcript.py <video_id> [lang1,lang2]"""
import sys
import json

def main():
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No video ID provided"}))
        sys.exit(1)

    video_id = sys.argv[1]
    langs    = sys.argv[2].split(',') if len(sys.argv) > 2 else ['hi', 'en']

    try:
        from youtube_transcript_api import YouTubeTranscriptApi
        api = YouTubeTranscriptApi()

        # Try requested languages, then any available
        transcript = None
        tried = []
        for lang in langs:
            try:
                transcript = api.fetch(video_id, languages=[lang])
                tried.append(lang)
                break
            except Exception:
                pass

        if transcript is None:
            transcript = api.fetch(video_id)

        text = ' '.join(s.text for s in transcript.snippets)
        text = ' '.join(text.split())  # normalise whitespace
        print(json.dumps({"text": text, "lang": tried[0] if tried else "auto"}))

    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)

if __name__ == '__main__':
    main()
