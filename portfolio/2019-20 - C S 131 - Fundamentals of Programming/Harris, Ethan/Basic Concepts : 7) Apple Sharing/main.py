N=input("How many goats?")
N=int(N)
K=input("How many apples?")
K=int(K)
answer1=(K/N)
answer1=int(answer1)
print("Each goat will receive", answer1, "apples.")
answer2=(K%N)
print(answer2, "apples will be left in the basket.")