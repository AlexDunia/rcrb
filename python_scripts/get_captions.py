import sys
from youtube_transcript_api import YouTubeTranscriptApi, TranscriptsDisabled, NoTranscriptFound

if __name__ == "__main__":
    try:
        # Get the video ID from the command-line argument
        video_id = sys.argv[1]

        # Fetch the transcript using get_transcripts
        transcript_data, _ = YouTubeTranscriptApi.get_transcripts([video_id])

        # Extract the transcript for the video_id
        transcript = transcript_data[video_id]

        # Join the text from the transcript parts
        full_text = ' '.join([item['text'] for item in transcript])

        # Print the final text to the console
        print(full_text)

    except TranscriptsDisabled:
        print("Error: Transcripts are disabled for this video.", file=sys.stderr)
        sys.exit(1)
    except NoTranscriptFound:
        print("Error: No transcript found for this video.", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)
