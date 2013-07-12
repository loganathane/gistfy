gistfy
======

A simple PHP class for summarizing text e.g. for automatically determining the sentences that are most relevant to the context of the corpus.


    Ranking sentences by importance using the core algorithm.
    Reorganizing the summary to focus on a topic; by selection of a keyword.
    Use both TextRank and LexRank

The core algorithm works by these simplified steps:
===================================================
    Associate words with their grammatical counterparts. (e.g. "city" and "cities")
    Calculate the occurrence of each word in the text.
    Assign each word with points depending on their popularity.
    Detect which periods represent the end of a sentence. (e.g "Mr." does not).
    Split up the text into individual sentences.
    Rank sentences by the sum of their words' points. and LexRank ponis
    Return X of the most highly ranked sentences in chronological order.
